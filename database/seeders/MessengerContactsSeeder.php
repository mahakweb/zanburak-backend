<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MessengerContactsSeeder extends Seeder
{
    /**
     * برای هر کاربر حدود ۴۰ مخاطب در پیام‌رسان.
     */
    public function run(): void
    {
        $now = now();
        $perUser = 40;

        $users = DB::table('users')
            ->orderBy('id')
            ->get(['id', 'first_name', 'last_name']);

        $count = $users->count();
        if ($count < 2) {
            return;
        }

        $take = min($perUser, $count - 1);
        $rows = [];

        foreach ($users as $index => $user) {
            for ($step = 1; $step <= $take; $step++) {
                $contact = $users[($index + $step) % $count];
                $rows[] = [
                    'user_id' => $user->id,
                    'contact_user_id' => $contact->id,
                    'name' => trim($contact->first_name.' '.$contact->last_name),
                    'is_blocked' => $step === $take,
                    'is_favorite' => $step <= 3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 400) as $chunk) {
            DB::table('contacts')->insert($chunk);
        }
    }
}
