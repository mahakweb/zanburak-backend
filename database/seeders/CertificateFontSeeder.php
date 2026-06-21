<?php

namespace Database\Seeders;

use App\Support\Certificate\CertificateFontService;
use Illuminate\Database\Seeder;

class CertificateFontSeeder extends Seeder
{
    public function run(): void
    {
        app(CertificateFontService::class)->ensureDefaults();
    }
}
