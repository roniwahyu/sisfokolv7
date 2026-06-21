# Workflow Migration Playbook (Dev Report 011)
## Proyek: Sistem Informasi Sekolah SMP Islam Terpadu (SIS SMP IT) SaaS
**Peran:** Enterprise Systems Architect, Lead Developer, & Technical Writer  
**Konteks:** Panduan Migrasi Alur Kerja Per Modul (Legacy Procedural → Laravel 11 Modular MVC)

---

## 1. Pendahuluan

Playbook ini disusun sebagai panduan langkah-demi-langkah bagi tim pengembang untuk memigrasikan alur kerja (*workflows*) dari modul-modul fungsional prosedural legacy (**SISFOKOL v7.00**) ke dalam arsitektur **Domain-Modular Monolith Laravel 11**.

Setiap modul dianalisis berdasarkan alur kerja lama, kelemahannya, dan bagaimana ia ditransformasikan secara aman dalam kode modern.

---

## 2. Alur Migrasi Kerja Per Modul (Module-by-Module Playbook)

### 2.1. Modul Autentikasi & Sesi (Auth Module)
*   **Alur Kerja Lama (Legacy):**
    *   File login (`login.php`) memproses request POST secara prosedural.
    *   Sandi pengguna diperiksa menggunakan query mentah `SELECT * FROM m_user WHERE usernamex = '$user' AND passwordx = md5('$pass')`.
    *   Status login disimpan dalam sesi PHP native statis.
    *   *Kelemahan:* Kerentanan SQL Injection tinggi; hash MD5 sangat lemah; tidak mendukung multi-tenancy (semua sekolah bercampur di satu tabel).
*   **Alur Kerja Baru (Laravel 11 Target):**
    *   Request masuk melalui `IdentifyTenant` middleware untuk mendeteksi `tenant_id` dari subdomain.
    *   `AuthController::login` menerima data aman, memvalidasi dengan request validator (`LoginRequest`), dan memverifikasi sandi menggunakan **Bcrypt** melalui `Auth::attempt()`.
    *   Sesi di-regenerasi secara instan (`$request->session()->regenerate()`) untuk mencegah serangan *session hijacking*.
    *   Setiap log aktivitas login dicatat otomatis ke tabel `user_log_login` yang terikat `tenant_id`.

### 2.2. Modul Akademik & Penjadwalan (Academic Module)
*   **Alur Kerja Lama (Legacy):**
    *   Tabel `m_siswa` menyimpan data siswa sekaligus data kelasnya saat itu sebagai string statis (`m_siswa.kelas = '7-A'`).
    *   Saat tahun ajaran berganti, data kelas ditimpa manual secara beramai-ramai.
    *   *Kelemahan:* Kehilangan riwayat (history) kelas siswa di tahun-tahun ajaran sebelumnya.
*   **Alur Kerja Baru (Laravel 11 Target):**
    *   Pemisahan data profil permanen siswa (`siswa` table) dengan data penempatan kelas berkala (`kelas_siswa` pivot table).
    *   Tabel `kelas_siswa` merekam: `siswa_id`, `kelas_id`, dan `no_urut` yang terikat pada `tahun_ajaran_id` tertentu.
    *   Guru, Ruangan, dan Jam Pelajaran direferensikan via kunci asing (FK) pada tabel `jadwal_pelajaran` untuk menjamin tidak ada bentrok jadwal harian.

### 2.3. Modul Evaluasi & Penilaian Kurikulum Merdeka (Evaluation Module)
*   **Alur Kerja Lama (Legacy):**
    *   Nilai formatif dan sumatif diinput di halaman terpisah yang memicu query INSERT berulang-ulang ke tabel denormalisasi `kurmer_nilai_asesmen_formatif_detail` dengan data siswa (nama, nis) yang di-copy-paste langsung di setiap baris nilai.
    *   *Kelemahan:* Ukuran database membengkak sangat cepat; perubahan nama siswa tidak akan mengupdate tabel nilai secara otomatis (inkonsistensi data).
*   **Alur Kerja Baru (Laravel 11 Target):**
    *   Nilai formatif (`asesmen_formatif_score`) dan sumatif (`asesmen_sumatif_score`) direlasikan langsung ke ID unik tabel pivot `kelas_siswa_id` (bukan string nama).
    *   Guru menginput nilai secara bulk melalui form interaktif. Sistem menghitung Nilai Akhir (NA) dan secara dinamis menyusun teks deskripsi capaian rapor menggunakan pemicu event *rapor generator*.

### 2.4. Modul Keuangan & Tabungan Siswa (Finance Module)
*   **Alur Kerja Lama (Legacy):**
    *   Saat kasir SPP dibuka, sistem melakukan query DELETE lalu INSERT masif untuk menyusun draf tagihan siswa di tabel `siswa_bayar_tagihan`.
    *   Nominal bayar dan sisa tunggakan disimpan sebagai tipe string (`varchar`) dan dihitung manual menggunakan fungsi `round()` PHP di dalam view script.
    *   *Kelemahan:* Risiko kegagalan data (data corruption) sangat tinggi jika koneksi mati di tengah jalan; performa database sangat buruk.
*   **Alur Kerja Baru (Laravel 11 Target):**
    *   Tagihan di-generate satu kali di awal bulan secara otomatis oleh cron job (`php artisan tagihan:generate`).
    *   Pencatatan pembayaran dilakukan di dalam blok transaksi database aman:
        ```php
        DB::transaction(function() use ($request) {
            // 1. Update nominal_terbayar di tabel tagihan_siswa (DECIMAL 12,2)
            // 2. Insert mutasi baru ke tabel transaksi_pembayaran (nomor_nota unik)
            // 3. Update status_lunas = 'Lunas' jika sisa kurang = 0
        });
        ```
    *   Sistem secara otomatis meng-generate QR Code kwitansi pembayaran unik untuk validasi fisik nota.

### 2.5. Modul Presensi & Piket Harian (Presence Module)
*   **Alur Kerja Lama (Legacy):**
    *   Presensi menggunakan scan barcode manual. Piket mencatat kejadian harian secara tertulis di buku besar, lalu merekapnya ulang ke file spreadsheet di akhir pekan.
*   **Alur Kerja Baru (Laravel 11 Target):**
    *   Siswa melakukan scan QR Code kartu digital mereka di gerbang sekolah.
    *   Scanner mengirim request API `POST /api/v1/presence/scan-qr`.
    *   Sistem membandingkan waktu kedatangan dengan batas jam masuk sekolah (misal 07:00), menghitung durasi keterlambatan secara presisi, lalu mencatat status kehadiran (`Hadir` atau `Terlambat` dengan durasi menit).
    *   Sistem memicu WhatsApp Gateway untuk mengirim notifikasi kehadiran real-time ke nomor handphone orang tua siswa.

### 2.6. Modul Kedisiplinan & BK (Discipline Module)
*   **Alur Kerja Lama (Legacy):**
    *   Pencatatan poin pelanggaran tidak terintegrasi langsung dengan pembinaan (BK mencatat di file manual terpisah).
*   **Alur Kerja Baru (Laravel 11 Target):**
    *   Piket/BK menginput pelanggaran siswa berdasarkan tabel master `bk_pelanggaran_master`.
    *   Sistem secara otomatis menjumlahkan akumulasi skor poin pelanggaran siswa.
    *   Jika akumulasi skor melebihi 100 poin, sistem secara otomatis memicu generator surat panggilan orang tua (PDF) dan mengunci akses cetak rapor siswa sementara sampai proses bimbingan selesai dicatat di tabel `siswa_pembinaan`.

---

## 3. Kesimpulan Playbook

Transformasi alur kerja ini memastikan sistem baru berjalan dengan performa maksimal, aman dari kebocoran data (*data breaches*), dan meminimalkan beban kerja staff administrasi sekolah melalui otomatisasi logika bisnis yang handal. Tim pengembang wajib mematuhi panduan alur kerja ini selama masa migrasi fitur berlangsung.
