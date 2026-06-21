# 11 — Arsitektur Sistem
### Proyek: Sistem Informasi Sekolah SMP Islam Terpadu

## 1. Pendahuluan

Dokumen ini menjelaskan arsitektur sistem yang diimplementasikan dari basis SISFOKOL v7.00. Sistem menggunakan arsitektur **Client-Server 3-Tier** dengan aplikasi web monolitik PHP Native yang berjalan di atas server XAMPP/LAMP.

## 2. Pola Arsitektur

- **3-Tier Architecture**: Presentasi (Browser) → Logika Aplikasi (PHP) → Data (MySQL).
- **Monolitik modular**: Satu basis kode, tetapi terpisah per modul peran (`adm`, `admks`, `admwk`, `admgr`, `admbk`, `admbdh`, `admpiket`, `admsw`, `adminv`).
- **Shared library**: Komponen umum (koneksi, konfigurasi, fungsi, template) berada di folder `inc/` dan `template/`.
- **Deployment**: On-premise (XAMPP) atau VPS/Cloud (LAMP).

## 3. Diagram Arsitektur Sistem (3-Tier)

```mermaid
flowchart TB
    subgraph KLIEN["TIER 1 — Klien / Presentation Layer"]
        direction LR
        WB[Web Browser<br/>Chrome/Edge/Firefox]
        MB[Mobile Browser<br/>Responsif]
        SC[Scanner QR<br/>Tablet/HP]
    end

    subgraph APP["TIER 2 — Server Aplikasi (PHP 8.2.4 / XAMPP)"]
        direction TB
        WEB[Apache/Nginx Web Server]
        PHP[PHP Native Engine]
        subgraph MOD["Modul Aplikasi"]
            M1[adm<br/>Admin/TU]
            M2[admks<br/>Kepsek]
            M3[admwk<br/>Wali Kelas]
            M4[admgr<br/>Guru]
            M5[admbk<br/>BK]
            M6[admbdh<br/>Bendahara]
            M7[admpiket<br/>Piket]
            M8[admsw<br/>Siswa]
            M9[adminv<br/>Sarpras]
        end
        LIB[inc/<br/>config, koneksi, fungsi, class]
        TPL[template/<br/>UI assets]
    end

    subgraph DATA["TIER 3 — Data Layer"]
        DB[(MySQL / MariaDB<br/>75 tabel)]
        FS[(File Storage<br/>filebox, img, db)]
    end

    subgraph EXT["Layanan Eksternal"]
        WA[WhatsApp API<br/>sosmedsekolah]
        XLS[Excel<br/>Impor/Ekspor]
    end

    KLIEN -->|HTTP/HTTPS| WEB
    WEB --> PHP --> MOD
    MOD --> LIB
    MOD --> TPL
    PHP -->|mysqli| DB
    PHP -->|file I/O| FS
    PHP <-->|API| WA
    PHP <--> XLS
```

## 4. Deskripsi Komponen

| Komponen | Teknologi | Fungsi |
|----------|-----------|--------|
| Klien | Browser web/mobile, scanner QR | Antarmuka pengguna & input |
| Web Server | Apache (XAMPP) / Nginx | Melayani permintaan HTTP/HTTPS |
| Application Server | PHP 8.2.4 (native) | Logika bisnis, modul per peran |
| Library Bersama | `inc/` (config, koneksi, fungsi) | Konektivitas DB, fungsi umum |
| Template/UI | `template/` (CSS/JS) | Tampilan & aset antarmuka |
| Database | MySQL/MariaDB | Penyimpanan 75 tabel |
| File Storage | `filebox/`, `img/`, `db/` | RPP, foto, QR, backup SQL |
| WhatsApp API | sosmedsekolah (opsional) | Notifikasi tagihan |
| Ekspor/Impor | Excel (.xls) | Migrasi & laporan |

## 5. Alur Permintaan (Request Flow)

```mermaid
sequenceDiagram
    participant U as Klien (Browser)
    participant A as Apache
    participant P as PHP (Modul)
    participant D as MySQL
    U->>A: Request HTTPS (login/menu)
    A->>P: Teruskan ke modul peran
    P->>P: Cek sesi (janiskd) + RBAC
    P->>D: Query (mysqli)
    D-->>P: Result set
    P-->>A: Render HTML (template)
    A-->>U: Halaman responsif
```

## 6. Arsitektur Deployment

```mermaid
flowchart LR
    subgraph ENV["Lingkungan"]
        DEV[Dev<br/>localhost XAMPP]
        STG[Staging<br/>VPS internal]
        PRD[Production<br/>VPS/Cloud + SSL]
    end
    DEV -->|deploy| STG -->|approve go-live| PRD
    PRD -->|backup harian| BK[(Backup Storage)]
```

## 7. Teknologi yang Digunakan (Ringkas)

| Lapisan | Teknologi | Versi |
|---------|-----------|-------|
| Bahasa | PHP (Native) | 8.2.4 |
| Database | MySQL / MariaDB | 5.7+ / 10.x |
| Web Server | Apache (XAMPP) | 2.4 |
| Frontend | HTML5, CSS3, JavaScript, jQuery | — |
| Template | Template bawaan SISFOKOL | v7 |
| Integrasi | WhatsApp API (sosmedsekolah) | opsional |
| Versioning | Git / GitLab | — |

> Detail lengkap teknologi ada di **Dokumen 17 — Spesifikasi Teknologi**.

## 8. Pertimbangan Arsitektural (Non-Fungsional)

- **Skalabilitas**: stateless PHP, mudah vertikal-scaling; dapat dipindah ke VPS lebih besar.
- **Keamanan**: HTTPS, RBAC per modul, hash password, prepared statement.
- **Ketersediaan**: backup harian + prosedur restore (lihat Dokumen 28).
- **Pemeliharaan**: kode terstruktur per modul & library bersama memudahkan pemeliharaan.

## 9. Penutup

Arsitektur 3-tier monolitik-modular dipilih karena sesuai platform SISFOKOL, ringan, murah, dan mudah dirawat oleh SDM IT sekolah. Sistem dapat dikembangkan ke arah layanan terpisah (microservices) jika beban tumbuh signifikan di masa depan.
