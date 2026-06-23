<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Field;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            ['kode' => 'siswa.nis',             'model' => 'App\Modules\Academic\Models\Siswa',          'kolom' => 'nis',           'label' => 'NIS',          'kategori' => 'normal',           'default_visibility' => 'visible'],
            ['kode' => 'siswa.nama',            'model' => 'App\Modules\Academic\Models\Siswa',          'kolom' => 'nama',          'label' => 'Nama',         'kategori' => 'normal',           'default_visibility' => 'visible'],
            ['kode' => 'siswa.telepon',         'model' => 'App\Modules\Academic\Models\Siswa',          'kolom' => 'telepon',       'label' => 'Telepon',      'kategori' => 'sensitif',         'default_visibility' => 'hidden'],
            ['kode' => 'siswa.alamat',          'model' => 'App\Modules\Academic\Models\Siswa',          'kolom' => 'alamat',        'label' => 'Alamat',       'kategori' => 'sensitif',         'default_visibility' => 'hidden'],
            ['kode' => 'siswa.tanggal_lahir',   'model' => 'App\Modules\Academic\Models\Siswa',          'kolom' => 'tanggal_lahir', 'label' => 'Tanggal Lahir','kategori' => 'sensitif',         'default_visibility' => 'hidden'],
            ['kode' => 'orang_tua.telepon',     'model' => 'App\Modules\Academic\Models\OrangTua',       'kolom' => 'telepon',       'label' => 'Telepon Ortu', 'kategori' => 'sangat_sensitif',  'default_visibility' => 'hidden'],
            ['kode' => 'orang_tua.email',       'model' => 'App\Modules\Academic\Models\OrangTua',       'kolom' => 'email',         'label' => 'Email Ortu',   'kategori' => 'sangat_sensitif',  'default_visibility' => 'hidden'],
            ['kode' => 'tagihan.nominal_kurang','model' => 'App\Modules\Finance\Models\TagihanSiswa',  'kolom' => 'nominal_kurang','label' => 'Tunggakan',    'kategori' => 'sangat_sensitif',  'default_visibility' => 'hidden'],
            ['kode' => 'pembayaran.total',      'model' => 'App\Modules\Finance\Models\Pembayaran',     'kolom' => 'total',         'label' => 'Total Bayar',  'kategori' => 'sangat_sensitif',  'default_visibility' => 'hidden'],
            ['kode' => 'tabungan.saldo',        'model' => 'App\Modules\Finance\Models\TabunganSiswa', 'kolom' => 'saldo',         'label' => 'Saldo',        'kategori' => 'sangat_sensitif',  'default_visibility' => 'hidden'],
        ];

        foreach ($fields as $f) {
            Field::firstOrCreate(['kode' => $f['kode']], $f);
        }
    }
}
