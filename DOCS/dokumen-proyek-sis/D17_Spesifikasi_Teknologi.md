# D17. Spesifikasi Teknologi

---

## Referensi dari Repo SISFOKOL v7.00 SmartOffice

Repositori referensi yang di-clone (`sisfokol-v7.00-code-smartoffice`) dibangun dengan **PHP Native 8.2.4** dan basis data **MySQL/MariaDB**. Proyek SMP Islam Terpadu dapat mengadopsi fondasi tersebut, atau mengembangkan ulang dengan framework modern (Laravel) untuk skalabilitas dan maintainability yang lebih baik.

---

## Tabel Teknologi yang Digunakan

| Layer | Teknologi | Versi | Fungsi / Alasan Pemilihan |
| --- | --- | --- | --- |
| Bahasa Pemrograman | PHP Native / JavaScript | 8.2 / ES6+ | SISFOKOL v7.00 menggunakan PHP Native 8.2.4 |
| Framework Backend | Laravel (rekomendasi) / PHP Native (referensi SISFOKOL) | 10.x / 8.2.4 | Laravel untuk maintainability; PHP Native jika mengadaptasi SISFOKOL |
| Framework Frontend | Blade + Bootstrap + jQuery | 5.x | Cepat dibangun, kompatibel mobile |
| Database | MySQL / MariaDB | 8.0 / 10.6 | Relasional, mudah dikelola, biaya terjangkau |
| Web Server | Nginx | 1.24+ | Ringan, performa tinggi, reverse proxy |
| Version Control | Git | 2.x | Kolaborasi dan tracking perubahan |
| Server OS | Ubuntu Server LTS | 22.04/24.04 | Stabil, dukungan komunitas luas |
| Container (opsional) | Docker | 24.x | Standarisasi environment dev/staging/prod |
| Report Engine | DomPDF / Laravel Excel | 2.x | Cetak PDF dan ekspor Excel |
| Cache | Redis | 7.x | Session dan caching query |
| Task Queue | Laravel Queue + Supervisor | - | Backup, notifikasi, export laporan |
| Monitoring | Laravel Telescope / Logrotate | - | Debug dan log monitoring |
| SMTP/Notifikasi | Mailgun / SMTP sekolah + WhatsApp Gateway | - | Notifikasi email dan WhatsApp |

## Justifikasi Pemilihan Teknologi

- **Laravel**: Memudahkan implementasi RBAC, ORM, validasi, dan testing.
- **MySQL/MariaDB**: Kompatibel dengan ekosistem hosting sekolah dan banyak referensi.
- **Bootstrap**: Mempercepat UI development dan responsif tanpa effort besar.
- **Nginx + PHP-FPM**: Performa baik untuk aplikasi dengan banyak user simultan.
- **Redis**: Menjaga performa session dan query yang sering diakses.

## Alternatif Teknologi

| Komponen | Alternatif | Kapan Digunakan |
| --- | --- | --- |
| Framework | Node.js + Express | Jika tim lebih kuat JavaScript |
| Database | PostgreSQL | Jika butuh fitur advanced dan concurrency tinggi |
| Frontend | Vue.js / React | Jika aplikasi ingin lebih interaktif di Fase 2 |
