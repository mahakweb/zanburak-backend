<?php

namespace Database\Seeders;

use App\Models\MailAccount;
use Illuminate\Database\Seeder;

class MailInboxAccountsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('mail-inbox.accounts', []) as $key => $account) {
            MailAccount::updateOrCreate(
                ['key' => $key],
                [
                    'address' => $account['address'] ?? "{$key}@zanburak.ir",
                    'label' => $account['label'] ?? $key,
                    'is_active' => true,
                ]
            );
        }
    }
}
