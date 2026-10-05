<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Services\Bank\IranianBankInspector;
use App\Support\IranianBanks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    public function __construct(private IranianBankInspector $inspector)
    {
    }

    public function index(Request $request)
    {
        $accounts = UserBankAccount::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'message' => 'Success',
            'accounts' => $accounts->map(fn (UserBankAccount $account) => $this->payload($account))->values(),
            'banks' => array_values(array_filter(array_map(
                fn ($code) => IranianBanks::find($code),
                array_keys(IranianBanks::all())
            ))),
        ]);
    }

    public function forUser(User $username)
    {
        $accounts = UserBankAccount::query()
            ->where('user_id', $username->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'message' => 'Success',
            'accounts' => $accounts->map(fn (UserBankAccount $account) => $this->payload($account))->values(),
            'banks' => array_values(array_filter(array_map(
                fn ($code) => IranianBanks::find($code),
                array_keys(IranianBanks::all())
            ))),
        ]);
    }

    public function updateForUser(Request $request, User $username, UserBankAccount $bankAccount)
    {
        $this->belongsTo($username, $bankAccount);
        $data = $request->validate([
            'kind' => 'required|in:sheba,card,account',
            'number' => 'required|string|max:40',
            'owner_name' => 'required|string|min:3|max:80',
            'bank_code' => 'nullable|string|max:3',
            'is_default' => 'nullable|boolean',
        ]);

        $result = $this->inspector->inspect($data['number'], $data['kind'], $data['bank_code'] ?? null);
        if (! $result['valid'] || ! $result['bank']) {
            return response()->json(['message' => $result['message']], 422);
        }
        if ($this->duplicate($username->id, $result['normalized'], $bankAccount->id)) {
            return response()->json(['message' => 'این شماره قبلاً ذخیره شده است.'], 422);
        }

        $makeDefault = (bool) ($data['is_default'] ?? false);
        DB::transaction(function () use ($username, $bankAccount, $data, $result, $makeDefault) {
            if ($makeDefault) {
                UserBankAccount::query()->where('user_id', $username->id)->update(['is_default' => false]);
            }
            $bankAccount->update([
                'kind' => $result['kind'],
                'bank_code' => $result['bank']['code'],
                'bank_name' => $result['bank']['name'],
                'number' => $result['normalized'],
                'owner_name' => trim($data['owner_name']),
                'is_default' => $makeDefault,
                'is_verified' => true,
            ]);
            if (! $makeDefault && ! UserBankAccount::query()->where('user_id', $username->id)->where('is_default', true)->exists()) {
                $bankAccount->update(['is_default' => true]);
            }
        });

        return response()->json([
            'message' => 'حساب بانکی ویرایش شد.',
            'account' => $this->payload($bankAccount->fresh()),
        ]);
    }

    public function destroyForUser(User $username, UserBankAccount $bankAccount)
    {
        $this->belongsTo($username, $bankAccount);
        $wasDefault = $bankAccount->is_default;
        $userId = $bankAccount->user_id;
        $bankAccount->delete();
        if ($wasDefault) {
            $next = UserBankAccount::query()->where('user_id', $userId)->orderByDesc('id')->first();
            $next?->update(['is_default' => true]);
        }

        return response()->json(['message' => 'حساب حذف شد.']);
    }

    public function inspect(Request $request)
    {
        $data = $request->validate([
            'kind' => 'required|in:sheba,card,account',
            'number' => 'nullable|string|max:40',
            'bank_code' => 'nullable|string|max:3',
        ]);

        return response()->json([
            'message' => 'Success',
            'result' => $this->inspector->inspect(
                (string) ($data['number'] ?? ''),
                $data['kind'],
                $data['bank_code'] ?? null
            ),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kind' => 'required|in:sheba,card,account',
            'number' => 'required|string|max:40',
            'owner_name' => 'required|string|min:3|max:80',
            'bank_code' => 'nullable|string|max:3',
            'is_default' => 'nullable|boolean',
        ]);

        $result = $this->inspector->inspect($data['number'], $data['kind'], $data['bank_code'] ?? null);
        if (! $result['valid'] || ! $result['bank']) {
            return response()->json(['message' => $result['message']], 422);
        }

        $user = $request->user();
        if ($this->duplicate($user->id, $result['normalized'])) {
            return response()->json(['message' => 'این شماره قبلاً ذخیره شده است.'], 422);
        }

        $makeDefault = (bool) ($data['is_default'] ?? false)
            || ! UserBankAccount::query()->where('user_id', $user->id)->exists();

        $account = DB::transaction(function () use ($user, $data, $result, $makeDefault) {
            if ($makeDefault) {
                UserBankAccount::query()->where('user_id', $user->id)->update(['is_default' => false]);
            }

            return UserBankAccount::create([
                'user_id' => $user->id,
                'kind' => $result['kind'],
                'bank_code' => $result['bank']['code'],
                'bank_name' => $result['bank']['name'],
                'number' => $result['normalized'],
                'owner_name' => trim($data['owner_name']),
                'is_default' => $makeDefault,
                'is_verified' => true,
            ]);
        });

        return response()->json([
            'message' => 'حساب بانکی ذخیره شد.',
            'account' => $this->payload($account),
        ]);
    }

    public function update(Request $request, UserBankAccount $bankAccount)
    {
        $this->owns($request, $bankAccount);
        $data = $request->validate([
            'kind' => 'required|in:sheba,card,account',
            'number' => 'required|string|max:40',
            'owner_name' => 'required|string|min:3|max:80',
            'bank_code' => 'nullable|string|max:3',
            'is_default' => 'nullable|boolean',
        ]);

        $result = $this->inspector->inspect($data['number'], $data['kind'], $data['bank_code'] ?? null);
        if (! $result['valid'] || ! $result['bank']) {
            return response()->json(['message' => $result['message']], 422);
        }
        if ($this->duplicate($request->user()->id, $result['normalized'], $bankAccount->id)) {
            return response()->json(['message' => 'این شماره قبلاً ذخیره شده است.'], 422);
        }

        $makeDefault = (bool) ($data['is_default'] ?? false) || $bankAccount->is_default;
        DB::transaction(function () use ($request, $bankAccount, $data, $result, $makeDefault) {
            if ($makeDefault) {
                UserBankAccount::query()->where('user_id', $request->user()->id)->update(['is_default' => false]);
            }
            $bankAccount->update([
                'kind' => $result['kind'],
                'bank_code' => $result['bank']['code'],
                'bank_name' => $result['bank']['name'],
                'number' => $result['normalized'],
                'owner_name' => trim($data['owner_name']),
                'is_default' => $makeDefault,
                'is_verified' => true,
            ]);
        });

        return response()->json([
            'message' => 'حساب بانکی ویرایش شد.',
            'account' => $this->payload($bankAccount->fresh()),
        ]);
    }

    public function makeDefault(Request $request, UserBankAccount $bankAccount)
    {
        $this->owns($request, $bankAccount);
        DB::transaction(function () use ($request, $bankAccount) {
            UserBankAccount::query()->where('user_id', $request->user()->id)->update(['is_default' => false]);
            $bankAccount->update(['is_default' => true]);
        });

        return response()->json(['message' => 'حساب پیش‌فرض شد.']);
    }

    public function destroy(Request $request, UserBankAccount $bankAccount)
    {
        $this->owns($request, $bankAccount);
        $wasDefault = $bankAccount->is_default;
        $userId = $bankAccount->user_id;
        $bankAccount->delete();
        if ($wasDefault) {
            $next = UserBankAccount::query()->where('user_id', $userId)->orderByDesc('id')->first();
            $next?->update(['is_default' => true]);
        }

        return response()->json(['message' => 'حساب حذف شد.']);
    }

    private function duplicate(int $userId, string $number, ?int $exceptId = null): bool
    {
        return UserBankAccount::query()
            ->where('user_id', $userId)
            ->where('number', $number)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }

    private function belongsTo(User $user, UserBankAccount $account): void
    {
        if ((int) $account->user_id !== (int) $user->id) {
            abort(404);
        }
    }

    private function owns(Request $request, UserBankAccount $account): void
    {
        if ((int) $account->user_id !== (int) $request->user()->id) {
            abort(403, 'این حساب متعلق به شما نیست.');
        }
    }

    private function payload(UserBankAccount $account): array
    {
        $bank = IranianBanks::find($account->bank_code);

        return [
            'id' => $account->id,
            'kind' => $account->kind,
            'number' => $account->number,
            'formatted' => $this->inspector->format($account->kind, $account->number),
            'owner_name' => $account->owner_name,
            'is_default' => (bool) $account->is_default,
            'is_verified' => (bool) $account->is_verified,
            'bank' => $bank,
        ];
    }
}
