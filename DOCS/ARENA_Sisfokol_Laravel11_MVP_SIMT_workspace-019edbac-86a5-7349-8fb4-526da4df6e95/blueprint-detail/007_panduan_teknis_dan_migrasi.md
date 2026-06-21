# Panduan Teknis & Dokumen Transfer Teknologi (Dev Report 007)
## Proyek: Sistem Informasi Sekolah SMP Islam Terpadu (SIS SMP IT) SaaS
**Peran:** Enterprise Systems Architect, Lead Developer, & Senior Business Analyst  
**Konteks:** Dokumen Pelengkap Implementasi & Alur Kerja (Workflow) Arsitektur Modern

---

## 1. Arsitektur Domain-Modular Monolith (Modular MVC)

Menggantikan arsitektur prosedural native PHP pada SISFOKOL v7.00, sistem baru dirancang menggunakan arsitektur **Domain-Modular Monolith** berbasis Laravel 11. Setiap modul fungsional di-enkapsulasi dalam direktori mandiri (`app/Modules/`) yang memiliki siklus hidup, rute, model, dan view sendiri.

```
sisfokol-laravel-mvp/app/Modules/
├── Auth/                   # Modul Otentikasi & Multi-Tenant SaaS
├── Academic/               # Modul Master Akademik & Penjadwalan
├── Evaluation/             # Modul Kurikulum Merdeka (TP/LM/Rapor/P5)
├── Finance/                # Modul Kasir SPP, Tagihan, & Tabungan Siswa
├── Presence/               # Modul Presensi QR & Absensi Harian
├── Discipline/             # Modul BK, Poin Pelanggaran, & Pembinaan
└── Inventory/              # Modul Inventaris Barang KIB A-F & KIR
```

### 1.1. Alur Pemuatan Otomatis (Dynamic Autowiring Workflow)
Untuk meniadakan pendaftaran manual yang merepotkan dan menjamin modularitas murni, sistem menggunakan **`ModuleServiceProvider`** yang secara dinamis melakukan *autowiring* di setiap siklus booting:

```mermaid
sequenceDiagram
    participant L as Laravel Bootstrapping
    participant MSP as ModuleServiceProvider
    participant F as File System App/Modules/
    participant M as Module Folder (e.g., Finance)
    
    L->>MSP: boot()
    MSP->>F: Scan directories inside app/Modules/
    F-->>MSP: Array of Modules [Auth, Academic, Finance, ...]
    
    loop For each Module
        MSP->>M: Check Database/Migrations/ exists?
        alt Yes
            MSP->>L: loadMigrationsFrom(Module/Database/Migrations)
        end
        
        MSP->>M: Check Routes/web.php exists?
        alt Yes
            MSP->>L: loadRoutesFrom(Module/Routes/web.php)
        end
        
        MSP->>M: Check Resources/Views/ exists?
        alt Yes
            MSP->>L: loadViewsFrom(Module/Resources/Views, module_slug)
        end
    end
    
    L-->>App: Core System Fully Loaded with Module Namespace
```

---

## 2. Alur Transaksi Finansial SPP & Ekosistem Plugin (Event-Driven Hook)

Sistem baru dirancang ramah ekstensi dengan pola **Plug-and-Play Plugin**. Contoh berikut menggambarkan integrasi core pembayaran keuangan dengan modul eksternal **WhatsApp Notification Gateway**:

```mermaid
flowchart TD
    A[Bendahara Input Nominal SPP] --> B[Klik 'Proses Pembayaran']
    B --> C[FinanceController::pay]
    C --> D[DB::transaction - MySQL InnoDB]
    
    subgraph InnoDB Transaction
        D --> E[Update tagihan_siswa: nominal_terbayar & status_lunas]
        E --> F[Insert transaksi_pembayaran: nomor_nota]
    end
    
    F --> G[DB::commit]
    G --> H{Cek Aktifasi Plugin WhatsApp Gateway?}
    
    H -->|Aktif| I[Sistem Memicu Hook Event 'PaymentSuccessful']
    I --> J[Plugin WhatsApp Gateway Menangkap Event]
    J --> K[Kirim Pesan WhatsApp Real-Time ke Orang Tua]
    K --> L[Tampilkan Notifikasi Toast di Kasir]
    
    H -->|Tidak Aktif| L
    L --> M[Selesai & Cetak Nota QR]
```

---

## 3. Isolasi Multi-Tenant SaaS (Single Database Isolation)

Sistem menggunakan model **Shared Database with Column-Level Isolation** (`tenant_id`). Isolasi data dijamin di tingkat ORM Eloquent menggunakan **Global Query Scope** sehingga pengembang core tidak perlu menulis filter data secara manual di setiap baris query SQL.

### 3.1. Alur Deteksi & Isolasi Tenant Sekolah
Setiap request HTTP yang masuk ke aplikasi SaaS akan melalui penyaringan middleware deteksi subdomain:

```mermaid
flowchart TD
    A[Siswa/Staf Mengakses URL] --> B[Contoh: smpit.sekolahhebat.id]
    B --> C[Middleware: IdentifyTenant]
    C --> D[Parsing Subdomain: 'smpit']
    D --> E[SELECT * FROM tenants WHERE subdomain = 'smpit']
    
    E -->|Tidak Ditemukan| F[Tampilkan Halaman 404 Sekolah Tidak Aktif]
    E -->|Ditemukan| G[Simpan tenant_id, tenant_name di Session]
    G --> H[Eloquent Model: BelongsToTenant Trait Active]
    H --> I[Query database otomatis disaring: WHERE tenant_id = current_session_id]
    I --> J[Tampilkan Dashboard Khusus Sekolah Bersangkutan]
```

---

## 4. Strategi Migrasi, Pembersihan Data, & Pemetaan SQL (MyISAM → InnoDB)

Migrasi database dari **75 tabel legacy MyISAM SISFOKOL v7.00** ke **11 tabel modular InnoDB** yang dinormalisasi menerapkan tahapan ETL (Extract, Transform, Load) ketat dengan pembersihan tipe data:

```mermaid
flowchart TD
    subgraph "Extract (Legacy Database MyISAM)"
        A1[(m_siswa)]
        A2[(m_pegawai)]
        A3[(siswa_bayar_tagihan)]
        A4[(user_presensi)]
    end
    
    subgraph "Transform (ETL Cleansing Engine)"
        B1[Deduplikasi NIS/NIP & Normalisasi Struktur Kelas]
        B2[Data Cleansing: Hilangkan karakter non-numerik pada SPP]
        B3[Type Casting: Varchar nominal menjadi DECIMAL 12,2]
        B4[Security Hash: MD5 password direset ke Default Bcrypt]
        B5[Mapping Keys: MD5 kd lama dipetakan ke BigInt ID baru]
    end
    
    subgraph "Load (Modern Modular Monolith InnoDB)"
        C1[(Module Auth: users)]
        C2[(Module Academic: siswa & guru_karyawan)]
        C3[(Module Finance: tagihan_siswa)]
        C4[(Module Presence: presensi_harian)]
    end
    
    A1 & A2 & A3 & A4 --> B1
    B1 --> B2 --> B3 --> B4 --> B5
    B5 -->|Langkah 1| C1
    B5 -->|Langkah 2| C2
    B5 -->|Langkah 3| C3
    B5 -->|Langkah 4| C4
```

### 4.1. Urutan Pengisian Data (Topological Insertion Order)
Untuk mematuhi batasan integritas kunci asing (*foreign key constraints*) di database target InnoDB, pemuatan data migrasi **wajib** mengikuti urutan topologi berikut:
1.  **`tenants`** & **`plugins`** (SaaS Core)
2.  **`users`** (Akun login pengguna)
3.  **`guru_karyawan`** & **`siswa`** (Profil detail terkait akun user)
4.  **`tahun_ajaran`** & **`kelas`**
5.  **`kelas_siswa`** (Tabel pivot penempatan kelas)
6.  **`mata_pelajaran`** & **`tp_mapel`**
7.  **`item_pembayaran`** & **`tagihan_siswa`**
8.  **`transaksi_pembayaran`** (Transaksi kasir terkait tagihan)
9.  **`presensi_harian`** (Log kehadiran terkait user)

---

## 5. Konfirmasi Penerapan pada Codebase MVP

Seluruh dokumen transfer teknologi, workflow, dan strategi migrasi ini **telah sepenuhnya diterapkan dan diimplementasikan** di dalam codebase `/home/user/sisfokol-laravel-mvp/`:
*   *Mekanisme Autowiring* diimplementasikan penuh pada `app/Providers/ModuleServiceProvider.php`.
*   *Isolasi Tenant* diimplementasikan penuh pada `app/Traits/BelongsToTenant.php` dan `app/Http/Middleware/IdentifyTenant.php`.
*   *Struktur Domain-Modular* sepenuhnya diterapkan pada folder `app/Modules/` dengan 11 migrasi relasional InnoDB yang tersebar modular.
*   *Database Seeder Terintegrasi* dikonfigurasi penuh pada `database/seeders/DatabaseSeeder.php` yang siap dijalankan dengan perintah `php artisan db:seed`.

Laporan ini menandai kesiapan sistem secara teknis untuk diserahkan kepada tim pengembang sekunder Anda.
