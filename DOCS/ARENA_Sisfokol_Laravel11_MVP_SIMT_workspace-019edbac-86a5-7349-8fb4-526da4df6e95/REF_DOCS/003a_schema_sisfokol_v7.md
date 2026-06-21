# Skema Database Modern Berbasis Multi-Tenant & Modular Plugin
## Dokumentasi Arsitektur Data Terintegrasi (Engine: InnoDB, MySQL 8.0)
**Konteks:** Cetak Biru Normalisasi Skema Database SIS SMP IT SaaS Enterprise  
**Peran:** Senior Database Architect & Enterprise Software Engineer

---

## 1. Arsitektur Database Multi-Tenant SaaS

Sistem ini didesain menggunakan model **Single Database dengan Logical Tenant Isolation** (Shared Schema). Setiap tabel transaksional dan master memiliki kolom `tenant_id` yang diindeks secara komposit untuk menjamin isolasi data mutlak antar sekolah (tenants) yang menyewa sistem.

### 1.1. Keuntungan Desain Shared Database ini:
1.  **Maintenance Efisien:** Cukup melakukan migrasi database sekali saja untuk memperbarui seluruh skema bagi ratusan sekolah.
2.  **Efisiensi Sumber Daya:** Meminimalkan penggunaan RAM dan memori server MySQL dibandingkan model *Multi-Database per Tenant*.
3.  **Kemudahan Scaling:** Penambahan sekolah baru hanya memerlukan penambahan satu baris pada tabel `tenants` tanpa perlu membuat database baru di server SQL.

---

## 2. Struktur Tabel Inti Manajemen Tenant & Plugin SaaS

Berikut adalah tabel fundamental untuk mengelola operasional Software-as-a-Service (SaaS):

### 2.1. Tabel `tenants` (Master Tenant Sekolah)
Menyimpan data identitas sekolah penyewa SaaS dan memetakan akses subdomain.

```sql
CREATE TABLE tenants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,                    -- Nama Sekolah (contoh: SMP IT Al-Karim)
    subdomain VARCHAR(50) NOT NULL UNIQUE,          -- Subdomain login (contoh: 'al-karim' -> al-karim.sekolahhebat.id)
    domain VARCHAR(100) NULL UNIQUE,                -- Custom domain (opsional, contoh: 'smpit.alkarim.sch.id')
    db_name VARCHAR(100) NULL,                     -- Digunakan jika nanti beralih ke model Multi-DB
    is_active BOOLEAN DEFAULT true,                 -- Status sewa/langganan sekolah
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_tenant_subdomain (subdomain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2. Tabel `plugins` (Master Registry Plugin Plug-and-Play)
Pendaftaran modul plugin eksternal yang tersedia di sistem inti.

```sql
CREATE TABLE plugins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,              -- Nama Plugin (contoh: 'WhatsApp Gateway Notification')
    slug VARCHAR(100) NOT NULL UNIQUE,              -- Slug plugin (contoh: 'whatsapp-gateway')
    version VARCHAR(10) NOT NULL DEFAULT '1.0.0',   -- Versi rilis plugin
    is_active_globally BOOLEAN DEFAULT true,        -- Status ketersediaan plugin secara sistem global
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.3. Tabel `tenant_plugins` (Konfigurasi Aktifasi Plugin Sekolah)
Tabel pivot yang merekam daftar plugin plug-and-play apa saja yang disewa dan aktif per sekolah.

```sql
CREATE TABLE tenant_plugins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    plugin_id BIGINT UNSIGNED NOT NULL,
    is_enabled BOOLEAN DEFAULT false,               -- Status aktif/nonaktif fitur di sekolah bersangkutan
    settings JSON NULL,                            -- Menyimpan konfigurasi khusus plugin (misal API key, nomor WA)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY uq_tenant_plugin (tenant_id, plugin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Skema Relasional Inti & Otorisasi Peran (Core Domain Tables)

Semua tabel transaksional di bawah ini wajib menyertakan indeks komposit `tenant_id` pada primary key/foreign key guna meningkatkan efisiensi pencarian database pada cluster besar.

### 3.1. Tabel `users` (Pusat Kredensial Multi-Role)
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,                      -- Peran utama login
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_tenant_username (tenant_id, username) -- Username unik terbatas dalam satu sekolah saja
) ENGINE=InnoDB;
```

### 3.2. Tabel `siswa` (Profil Detail Ter-normalisasi)
Menggantikan model legacy yang menyatukan login credential dan profil siswa di satu tabel.

```sql
CREATE TABLE siswa (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    nisn VARCHAR(10) NULL,                          -- NISN Nasional (opsional unik)
    nis VARCHAR(20) NOT NULL,                       -- NIS Lokal Sekolah
    nama_lengkap VARCHAR(150) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    tempat_lahir VARCHAR(100) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    alamat TEXT NOT NULL,
    no_wa_siswa VARCHAR(20) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_tenant_nis (tenant_id, nis)       -- NIS harus unik dalam sekolah yang bersangkutan
) ENGINE=InnoDB;
```

### 3.3. Tabel `tagihan_siswa` (Kewajiban Finansial SPP/Iuran)
```sql
CREATE TABLE tagihan_siswa (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    siswa_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,               -- Relasi ke master item_pembayaran
    bulan_ke INT NOT NULL,                          -- Bulanan ke- (1=Juli, 12=Juni)
    nominal_tagihan DECIMAL(12,2) NOT NULL,         -- Varchar legacy dikonversi ke Decimal presisi tinggi
    nominal_terbayar DECIMAL(12,2) DEFAULT 0.00,
    status_lunas ENUM('Lunas', 'Belum') DEFAULT 'Belum',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    INDEX idx_tenant_status (tenant_id, status_lunas)
) ENGINE=InnoDB;
```

---

## 4. Mekanisme Keamanan Integritas & Transaksi Data

1.  **ROW-LEVEL LOCKING (InnoDB Engine):** 
    Menjamin tidak terjadi tabrakan penomoran kwitansi/nota SPP harian saat banyak kasir melakukan input pembayaran secara simultan.
2.  **SOFT DELETES (`deleted_at`):**
    Siswa yang pindah atau mutasi tidak boleh dihapus secara permanen dari database guna menjaga histori nilai akademik dan histori setoran SPP demi audit keuangan sekolah tahunan.
3.  **JSON AUDIT LOGGING:**
    Setiap perubahan nilai rapor atau transaksi keuangan wajib merekam payload JSON data sebelum dan sesudah perubahan di tabel `audit_logs` untuk mematuhi regulasi perlindungan data pendidikan.
