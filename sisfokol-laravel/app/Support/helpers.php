<?php
/**
 * SISFOKOL Global Helpers
 * Di-autoload via composer.json "files" key.
 * Tersedia di seluruh aplikasi tanpa perlu import.
 */

use Illuminate\Database\Schema\Blueprint;

if (! function_exists('tenant_and_audit_columns')) {
    /**
     * ADR-007: Helper untuk kolom boilerplate di setiap tabel domain.
     * Pakai di migration: $table->id(); tenant_and_audit_columns($table); ...
     */
    function tenant_and_audit_columns(Blueprint $table, bool $withSoftDelete = true): void
    {
        $table->unsignedBigInteger('tenant_id')->index();
        $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

        if ($withSoftDelete) {
            $table->softDeletes();
        }

        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    }
}

if (! function_exists('audit_columns')) {
    /**
     * Untuk tabel yang TIDAK butuh tenant_id (mis. tenants, branches, plugins global).
     */
    function audit_columns(Blueprint $table, bool $withSoftDelete = true): void
    {
        if ($withSoftDelete) {
            $table->softDeletes();
        }
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    }
}

if (! function_exists('clean_money')) {
    /**
     * Bersihkan string uang legacy (varchar "Rp. 150.000") → float 150000.00
     * Dipakai di ETL pipeline untuk konversi nominal_bayar, nominal_kurang, dll.
     */
    function clean_money(?string $value): float
    {
        if ($value === null || trim($value) === '') {
            return 0.00;
        }
        // Hapus semua karakter non-numerik kecuali koma dan titik
        $clean = preg_replace('/[^0-9,.]/', '', $value);
        if ($clean === '' || $clean === null) {
            return 0.00;
        }
        // Deteksi format ribuan Indonesia: "1.500.000,50"
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $clean)) {
            // Format ribuan dengan titik, desimal dengan koma
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (preg_match('/^\d+,\d{1,2}$/', $clean)) {
            // Format desimal koma saja: "75,50"
            $clean = str_replace(',', '.', $clean);
        } else {
            // Bila multi-titik tanpa pola ribuan yang jelas: ambil angka saja
            $clean = str_replace(['.', ','], '', $clean);
        }
        return round((float) $clean, 2);
    }
}

if (! function_exists('clean_date')) {
    /**
     * Normalisasi tanggal legacy ke format Y-m-d.
     * Mendukung: "2024-07-15", "15-07-2024", "15/07/2024"
     * Return null untuk tanggal kosong atau invalid (termasuk "0000-00-00").
     */
    function clean_date(?string $value): ?string
    {
        if ($value === null || trim($value) === '' || $value === '0000-00-00') {
            return null;
        }
        $value = trim($value);
        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $value);
            if ($dt && $dt->format($format) === $value) {
                return $dt->format('Y-m-d');
            }
        }
        return null; // Log warning di caller untuk manual reconcile
    }
}

if (! function_exists('clean_phone')) {
    /**
     * Normalisasi nomor telepon ke format internasional Indonesia (62xxx).
     * Input: "081234567890", "+6281234567890", "81234567890"
     * Output: "6281234567890" atau null bila kosong.
     */
    function clean_phone(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $clean = preg_replace('/[^0-9]/', '', $value);
        if ($clean === '' || $clean === null) {
            return null;
        }
        if (str_starts_with($clean, '0')) {
            return '62' . substr($clean, 1);
        }
        if (str_starts_with($clean, '62')) {
            return $clean;
        }
        if (str_starts_with($clean, '8')) {
            return '62' . $clean;
        }
        return $clean;
    }
}

if (! function_exists('carbon_month_name')) {
    /**
     * Dapatkan nama bulan dalam bahasa Indonesia secara konsisten.
     */
    function carbon_month_name(int $month): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $months[$month] ?? '';
    }
}