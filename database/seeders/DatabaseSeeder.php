<?php

namespace Database\Seeders;

use App\Enums\Peran;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data dasar: OPD percontohan dan akun awal.
     *
     * Kata sandi awal diambil dari SEED_PASSWORD di .env (bawaan: "password").
     * WAJIB diganti sebelum aplikasi dipasang di server.
     */
    public function run(): void
    {
        $pupr = Opd::updateOrCreate(['singkatan' => 'PUPR'], ['nama' => 'Dinas Pekerjaan Umum dan Penataan Ruang']);
        $dinkes = Opd::updateOrCreate(['singkatan' => 'DINKES'], ['nama' => 'Dinas Kesehatan']);
        Opd::updateOrCreate(['singkatan' => 'DISKOMINFO'], ['nama' => 'Dinas Komunikasi dan Informatika']);

        $sandi = env('SEED_PASSWORD', 'password');

        $akun = [
            ['email' => 'admin@katalog.test', 'name' => 'Admin Aptika', 'role' => Peran::Admin, 'opd_id' => null],
            ['email' => 'walidata@katalog.test', 'name' => 'Walidata Diskominfo', 'role' => Peran::Walidata, 'opd_id' => null],
            ['email' => 'operator.pupr@katalog.test', 'name' => 'Operator PUPR', 'role' => Peran::Operator, 'opd_id' => $pupr->id],
            ['email' => 'operator.dinkes@katalog.test', 'name' => 'Operator Dinkes', 'role' => Peran::Operator, 'opd_id' => $dinkes->id],
        ];

        foreach ($akun as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                $data + ['password' => $sandi, 'aktif' => true],
            );
        }

        if (app()->environment('local')) {
            $this->call(ContohDatasetSeeder::class);
        }
    }
}
