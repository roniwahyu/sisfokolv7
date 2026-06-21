# B11. Arsitektur Sistem

---

## Arsitektur 3-Tier

```mermaid
flowchart TB
    subgraph "Presentation Layer"
        A[Web Browser / Mobile Browser]
        B[Portal Web — HTML/CSS/JS + Bootstrap]
    end
    subgraph "Application Layer"
        C[Web Server — Nginx/Apache]
        D[Backend Framework — PHP/Laravel/Node.js]
        E[Business Logic — RBAC, Akademik, Keuangan]
    end
    subgraph "Data Layer"
        F[(Database — MySQL/PostgreSQL)]
        G[File Storage — PDF Reports, Photos]
    end
    A --> C --> D --> E --> F
    E --> G
```

## Penjelasan Layer

| Layer | Teknologi | Fungsi |
| --- | --- | --- |
| Presentation | Browser, HTML5, CSS3, JavaScript, Bootstrap | Menampilkan antarmuka pengguna, responsif desktop & mobile. |
| Application | Nginx/Apache, PHP 8.x / Laravel / Node.js | Menangani logika bisnis, autentikasi, RBAC, dan API. |
| Data | MySQL 8 / MariaDB 10 / PostgreSQL 13 | Menyimpan data master, transaksi, log, dan laporan. |
| External | SMTP, WhatsApp Gateway (opsional) | Mengirim notifikasi dan pengumuman. |

## Deployment Model

```mermaid
flowchart LR
    A[Internet] --> B[Firewall / Router Sekolah]
    B --> C[Reverse Proxy Nginx]
    C --> D[App Server 1]
    C --> E[App Server 2 — Opsional]
    D --> F[(Database Server)]
    E --> F
    F --> G[Backup Server / Cloud Storage]
```

## Keunggulan Arsitektur

- **Pemisahan concerns**: Mudah dikembangkan dan diuji.
- **Skalabilitas**: App server dapat ditambah jika jumlah pengguna meningkat.
- **Keamanan**: Database tidak terpapar langsung ke internet.
- **Maintainability**: Modul dapat diperbarui tanpa mengganggu layer lain.
