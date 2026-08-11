<?php

namespace App\Services\Messenger;

use App\Models\Contact;
use App\Models\MessengerSetting;
use App\Models\PrivacyException;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Telegram-style privacy: everybody / contacts / nobody + allow/deny exception lists.
 * Results are cached briefly to keep list/profile/presence serialization cheap.
 */
class PrivacyService
{
    public const KEYS = ['last_seen', 'online', 'profile_photo', 'bio', 'phone'];

    public const RULES = ['everybody', 'contacts', 'nobody'];

    public const TTL_SECONDS = 60;

    /** @var array<string, mixed> Request-local memoization. */
    protected array $memo = [];

    public function ruleFor(User $owner, string $key): string
    {
        $settings = $owner->resolvedMessengerSettings();
        $column = 'privacy_'.$key;
        $raw = $settings->{$column} ?? null;

        if (is_string($raw) && in_array($raw, self::RULES, true)) {
            return $raw;
        }

        // Legacy boolean fallbacks.
        return match ($key) {
            'last_seen' => $settings->show_last_seen ? 'everybody' : 'nobody',
            'online' => $settings->show_online ? 'everybody' : 'nobody',
            'phone' => $settings->show_phone ? 'everybody' : 'nobody',
            default => 'everybody',
        };
    }

    /**
     * Whether $viewer may see $owner's $key field (owner settings + exceptions).
     * Self always allowed. Null viewer = public/anonymous → everybody only.
     */
    public function canSee(User $owner, ?User $viewer, string $key): bool
    {
        if (! in_array($key, self::KEYS, true)) {
            return false;
        }

        if (! $viewer) {
            return $this->ruleFor($owner, $key) === 'everybody';
        }

        if ((int) $owner->id === (int) $viewer->id) {
            return true;
        }

        $memoKey = 'can:'.$owner->id.':'.$viewer->id.':'.$key;
        if (array_key_exists($memoKey, $this->memo)) {
            return $this->memo[$memoKey];
        }

        $exception = $this->exceptionRule($owner->id, $key, $viewer->id);
        if ($exception === PrivacyException::RULE_DENY) {
            return $this->memo[$memoKey] = false;
        }
        if ($exception === PrivacyException::RULE_ALLOW) {
            return $this->memo[$memoKey] = true;
        }

        $rule = $this->ruleFor($owner, $key);
        $result = match ($rule) {
            'everybody' => true,
            'nobody' => false,
            'contacts' => $this->isContactOf($owner->id, $viewer->id),
            default => false,
        };

        return $this->memo[$memoKey] = $result;
    }

    /**
     * Mutual last-seen / online (Telegram): if either side hides from the other,
     * exact status is not shared.
     */
    public function canSeePresence(User $owner, ?User $viewer): bool
    {
        if (! $viewer || (int) $owner->id === (int) $viewer->id) {
            return $this->canSee($owner, $viewer, 'last_seen');
        }

        return $this->canSee($owner, $viewer, 'last_seen')
            && $this->canSee($viewer, $owner, 'last_seen');
    }

    public function canSeeOnline(User $owner, ?User $viewer): bool
    {
        if (! $viewer || (int) $owner->id === (int) $viewer->id) {
            return $this->canSee($owner, $viewer, 'online');
        }

        // Online follows both online rule and mutual last-seen reciprocity.
        return $this->canSee($owner, $viewer, 'online')
            && $this->canSee($viewer, $owner, 'online')
            && $this->canSeePresence($owner, $viewer);
    }

    public function visibleOnline(User $owner, ?User $viewer, ?bool $forceOnline = null): bool
    {
        if (! $this->canSeeOnline($owner, $viewer)) {
            return false;
        }

        return $forceOnline !== null ? $forceOnline : $owner->isOnline();
    }

    public function visibleLastSeen(User $owner, ?User $viewer): ?string
    {
        if (! $this->canSeePresence($owner, $viewer)) {
            return null;
        }

        return $owner->last_seen
            ? Carbon::parse($owner->last_seen)->toIso8601String()
            : null;
    }

    public function visibleProfilePhoto(User $owner, ?User $viewer): ?string
    {
        if (! $this->canSee($owner, $viewer, 'profile_photo')) {
            return null;
        }

        return $owner->profile_pic;
    }

    public function visibleBio(User $owner, ?User $viewer): ?string
    {
        if (! $this->canSee($owner, $viewer, 'bio')) {
            return null;
        }

        return $owner->bio;
    }

    public function visiblePhone(User $owner, ?User $viewer): ?string
    {
        if (! $this->canSee($owner, $viewer, 'phone')) {
            return null;
        }

        return $owner->mobile;
    }

    /**
     * Fast path for presence fan-out: warm contact + exception caches for one owner.
     *
     * @param  array<int>  $audienceIds
     */
    public function warmForBroadcast(User $owner, array $audienceIds): void
    {
        $this->contactIdsOf($owner->id);
        foreach (['last_seen', 'online'] as $key) {
            $this->exceptionMap($owner->id, $key);
        }
        // Audience may also need their own settings for mutual checks.
        foreach ($audienceIds as $aid) {
            $aid = (int) $aid;
            $this->contactIdsOf($aid);
            foreach (['last_seen', 'online'] as $key) {
                $this->exceptionMap($aid, $key);
            }
        }
    }

    public function invalidateUser(int $userId): void
    {
        Cache::forget($this->contactsCacheKey($userId));
        foreach (self::KEYS as $key) {
            Cache::forget($this->exceptionsCacheKey($userId, $key));
        }
        $this->memo = [];
    }

    /**
     * Whether $targetUserId is in $ownerId's contact list (not blocked).
     */
    public function isContactOf(int $ownerId, int $targetUserId): bool
    {
        return isset($this->contactIdsOf($ownerId)[$targetUserId]);
    }

    /**
     * @return array<int, true> contact_user_id => true
     */
    protected function contactIdsOf(int $ownerId): array
    {
        $memoKey = 'contacts:'.$ownerId;
        if (isset($this->memo[$memoKey])) {
            return $this->memo[$memoKey];
        }

        $ids = Cache::remember($this->contactsCacheKey($ownerId), self::TTL_SECONDS, function () use ($ownerId) {
            return Contact::query()
                ->where('user_id', $ownerId)
                ->where('is_blocked', false)
                ->pluck('contact_user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        });

        $map = [];
        foreach ($ids as $id) {
            $map[(int) $id] = true;
        }

        return $this->memo[$memoKey] = $map;
    }

    protected function exceptionRule(int $ownerId, string $key, int $viewerId): ?string
    {
        $map = $this->exceptionMap($ownerId, $key);

        return $map[$viewerId] ?? null;
    }

    /**
     * @return array<int, string> target_user_id => allow|deny
     */
    protected function exceptionMap(int $ownerId, string $key): array
    {
        $memoKey = 'exc:'.$ownerId.':'.$key;
        if (isset($this->memo[$memoKey])) {
            return $this->memo[$memoKey];
        }

        $rows = Cache::remember($this->exceptionsCacheKey($ownerId, $key), self::TTL_SECONDS, function () use ($ownerId, $key) {
            return PrivacyException::query()
                ->where('user_id', $ownerId)
                ->where('setting_key', $key)
                ->get(['target_user_id', 'rule'])
                ->mapWithKeys(fn ($r) => [(int) $r->target_user_id => (string) $r->rule])
                ->all();
        });

        return $this->memo[$memoKey] = $rows;
    }

    protected function contactsCacheKey(int $userId): string
    {
        return "messenger:privacy:contacts:{$userId}";
    }

    protected function exceptionsCacheKey(int $userId, string $key): string
    {
        return "messenger:privacy:exc:{$userId}:{$key}";
    }

    /**
     * Persist privacy rules + exception lists; sync legacy booleans.
     *
     * @param  array<string, mixed>  $privacy
     */
    public function updatePrivacy(User $user, MessengerSetting $settings, array $privacy): void
    {
        $payload = [];

        foreach (self::KEYS as $key) {
            if (! isset($privacy[$key]) || ! is_array($privacy[$key])) {
                continue;
            }
            $slice = $privacy[$key];
            if (isset($slice['rule']) && in_array($slice['rule'], self::RULES, true)) {
                $payload['privacy_'.$key] = $slice['rule'];
            }

            if (array_key_exists('always_allow', $slice) || array_key_exists('never_allow', $slice)) {
                $this->syncExceptions(
                    $user->id,
                    $key,
                    array_map('intval', (array) ($slice['always_allow'] ?? [])),
                    array_map('intval', (array) ($slice['never_allow'] ?? []))
                );
            }
        }

        if ($payload !== []) {
            // Keep legacy flags in sync for older clients / code paths.
            if (isset($payload['privacy_last_seen'])) {
                $payload['show_last_seen'] = $payload['privacy_last_seen'] !== 'nobody';
            }
            if (isset($payload['privacy_online'])) {
                $payload['show_online'] = $payload['privacy_online'] !== 'nobody';
            }
            if (isset($payload['privacy_phone'])) {
                $payload['show_phone'] = $payload['privacy_phone'] !== 'nobody';
            }
            $settings->update($payload);
        }

        $this->invalidateUser($user->id);
    }

    /**
     * @param  array<int>  $allowIds
     * @param  array<int>  $denyIds
     */
    protected function syncExceptions(int $userId, string $key, array $allowIds, array $denyIds): void
    {
        $allowIds = array_values(array_unique(array_filter($allowIds, fn ($id) => $id > 0 && $id !== $userId)));
        $denyIds = array_values(array_unique(array_filter($denyIds, fn ($id) => $id > 0 && $id !== $userId)));
        // Deny wins if listed in both.
        $allowIds = array_values(array_diff($allowIds, $denyIds));

        PrivacyException::where('user_id', $userId)->where('setting_key', $key)->delete();

        $now = now();
        $rows = [];
        foreach ($allowIds as $tid) {
            $rows[] = [
                'user_id' => $userId,
                'setting_key' => $key,
                'target_user_id' => $tid,
                'rule' => PrivacyException::RULE_ALLOW,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach ($denyIds as $tid) {
            $rows[] = [
                'user_id' => $userId,
                'setting_key' => $key,
                'target_user_id' => $tid,
                'rule' => PrivacyException::RULE_DENY,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows !== []) {
            PrivacyException::insert($rows);
        }

        Cache::forget($this->exceptionsCacheKey($userId, $key));
    }

    /**
     * Build privacy payload for settings API (rules + exception user briefs).
     *
     * @return array<string, mixed>
     */
    public function settingsPayload(User $user, MessengerSetting $settings): array
    {
        $out = [];
        foreach (self::KEYS as $key) {
            $column = 'privacy_'.$key;
            $rule = is_string($settings->{$column} ?? null) && in_array($settings->{$column}, self::RULES, true)
                ? $settings->{$column}
                : $this->ruleFor($user, $key);

            $exceptions = PrivacyException::query()
                ->where('user_id', $user->id)
                ->where('setting_key', $key)
                ->with(['targetUser:id,first_name,last_name,username,profile_pic,last_seen'])
                ->get();

            $always = [];
            $never = [];
            foreach ($exceptions as $ex) {
                if (! $ex->targetUser) {
                    continue;
                }
                $brief = [
                    'id' => $ex->targetUser->id,
                    'first_name' => $ex->targetUser->first_name,
                    'last_name' => $ex->targetUser->last_name,
                    'username' => $ex->targetUser->username,
                    'profile_pic' => $ex->targetUser->profile_pic,
                ];
                if ($ex->rule === PrivacyException::RULE_ALLOW) {
                    $always[] = $brief;
                } else {
                    $never[] = $brief;
                }
            }

            $out[$key] = [
                'rule' => $rule,
                'always_allow' => $always,
                'never_allow' => $never,
            ];
        }

        return $out;
    }
}
