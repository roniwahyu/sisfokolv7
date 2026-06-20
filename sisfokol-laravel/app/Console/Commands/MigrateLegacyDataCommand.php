<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateLegacyDataCommand extends Command
{
    protected $signature = 'migrate:legacy
                            {--source=sisfokol_v7 : Nama database sumber}
                            {--target=sisfokol_laravel : Nama database target}
                            {--dry-run : Jalankan tanpa menulis ke target}';

    protected $description = 'Migrasi data dari SISFOKOL v7 ke database Laravel 11 baru';

    public function handle(): int
    {
        $source = $this->option('source');
        $target = $this->option('target');
        $dryRun = $this->option('dry-run');

        $this->info("Memulai migrasi data dari {$source} ke {$target}");

        if ($dryRun) {
            $this->warn('Mode dry-run: tidak ada data yang akan ditulis.');
        }

        // TODO: Implementasi migrasi per tabel
        // 1. Master data (tapel, kelas, ruang, mapel, jenis, dll)
        // 2. Kepegawaian & kesiswaan (pegawai, siswa, wali, bk, dll)
        // 3. Akademik & jadwal
        // 4. Transaksi (presensi, absensi, pelanggaran, keuangan, inventaris)

        $this->info('Migrasi selesai (placeholder).');

        return self::SUCCESS;
    }
}
