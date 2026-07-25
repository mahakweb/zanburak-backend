<?php

namespace App\Services\Messenger;

use App\Models\Conversation;
use App\Models\ConversationRolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class GroupPermissionService
{
    /**
     * Whether $user may perform $permission in $conversation.
     */
    public function can(User $user, Conversation $conversation, string $permission): bool
    {
        if (! $conversation->isCommunity()) {
            return true;
        }

        $pivot = $conversation->memberPivot($user);
        if (! $pivot || $pivot->is_banned || ! $pivot->is_active || $pivot->deleted_at) {
            return false;
        }

        $role = (string) $pivot->role;

        return $this->roleAllows($conversation, $role, $permission);
    }

    public function assertCan(User $user, Conversation $conversation, string $permission): void
    {
        if (! $this->can($user, $conversation, $permission)) {
            throw new \RuntimeException('Forbidden');
        }
    }

    public function roleAllows(Conversation $conversation, string $role, string $permission): bool
    {
        $matrix = $this->matrixFor($conversation);
        $rolePerms = $matrix[$role] ?? [];

        if ($rolePerms === '*' || (is_array($rolePerms) && in_array('*', $rolePerms, true))) {
            return true;
        }

        if (! is_array($rolePerms)) {
            return false;
        }

        return in_array($permission, $rolePerms, true);
    }

    /**
     * Effective permission list per role for a conversation (defaults + overrides).
     *
     * @return array<string, array<int, string>|string>
     */
    public function matrixFor(Conversation $conversation): array
    {
        $type = $conversation->isChannel() ? 'channel' : 'group';
        $defaults = config("messenger_groups.defaults.{$type}", []);

        $cacheKey = "messenger:perms:{$conversation->id}";

        $overrides = Cache::remember($cacheKey, 60, function () use ($conversation) {
            return ConversationRolePermission::query()
                ->where('conversation_id', $conversation->id)
                ->get()
                ->groupBy('role');
        });

        $matrix = [];
        foreach ($defaults as $role => $perms) {
            if ($perms === '*') {
                $matrix[$role] = '*';

                continue;
            }

            $allowed = collect($perms);
            $roleOverrides = $overrides->get($role, collect());

            foreach ($roleOverrides as $row) {
                if ($row->allowed) {
                    $allowed->push($row->permission);
                } else {
                    $allowed = $allowed->reject(fn ($p) => $p === $row->permission);
                }
            }

            $matrix[$role] = $allowed->unique()->values()->all();
        }

        return $matrix;
    }

    public function forgetCache(Conversation $conversation): void
    {
        Cache::forget("messenger:perms:{$conversation->id}");
    }

    /**
     * Set an override for a role permission in a conversation.
     */
    public function setPermission(Conversation $conversation, string $role, string $permission, bool $allowed): void
    {
        $valid = config('messenger_groups.permissions', []);
        if (! in_array($permission, $valid, true)) {
            throw new \InvalidArgumentException("Unknown permission: {$permission}");
        }

        $roles = config('messenger_groups.roles.'.($conversation->isChannel() ? 'channel' : 'group'), []);
        if (! in_array($role, $roles, true)) {
            throw new \InvalidArgumentException("Unknown role: {$role}");
        }

        if ($role === 'owner') {
            throw new \InvalidArgumentException('Owner permissions cannot be changed');
        }

        ConversationRolePermission::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'role' => $role,
                'permission' => $permission,
            ],
            ['allowed' => $allowed]
        );

        $this->forgetCache($conversation);
    }

    /**
     * Role hierarchy for moderation (higher number = more power).
     */
    public function roleRank(string $role): int
    {
        return match ($role) {
            'owner' => 100,
            'admin' => 80,
            'moderator' => 60,
            'member', 'subscriber' => 40,
            'guest' => 20,
            default => 0,
        };
    }

    public function canModerate(User $actor, Conversation $conversation, User $target): bool
    {
        $actorRole = $conversation->memberRole($actor);
        $targetRole = $conversation->memberRole($target);

        if (! $actorRole || ! $targetRole) {
            return false;
        }

        if ($targetRole === 'owner') {
            return false;
        }

        return $this->roleRank($actorRole) > $this->roleRank($targetRole);
    }
}
