# D19. Konfigurasi Environment

---

## Tabel Environment

| Komponen | Development | Staging | Production |
| --- | --- | --- | --- |
| **Tujuan** | Pengembangan fitur & testing unit | UAT & QA | Live untuk pengguna sekolah |
| **Server** | Local / Docker / XAMPP PHP 8.2.4 | VPS/cloud | VPS/cloud dedicated |
| **OS** | Ubuntu / macOS / Windows WSL | Ubuntu Server LTS | Ubuntu Server LTS |
| **Web Server** | PHP built-in / Nginx | Nginx | Nginx + SSL Let's Encrypt |
| **PHP** | 8.2 | 8.2 | 8.2 |
| **Database** | MySQL 8 / SQLite | MySQL 8 | MySQL 8 / MariaDB 10.6 |
| **Redis** | Opsional | Redis | Redis |
| **APP_ENV** | local | staging | production |
| **APP_DEBUG** | true | false | false |
| **Log Level** | debug | info | warning/error |
| **Backup** | Manual | Harian otomatis | Harian + mingguan |
| **Akses** | Developer | Internal + tester | Semua stakeholder |

## Konfigurasi Server Minimal Production

| Sumber Daya | Spesifikasi |
| --- | --- |
| CPU | 4 vCPU |
| RAM | 8 GB |
| Storage | 100 GB SSD |
| Bandwidth | 100 Mbps |
| Database | Dedicated instance atau minimal berbeda user |

## Daftar Variabel Environment Penting

| Variabel | Keterangan | Contoh |
| --- | --- | --- |
| APP_NAME | Nama aplikasi | "SMP IT SIS" |
| APP_ENV | Environment | production |
| APP_KEY | Key enkripsi Laravel | base64:... |
| DB_HOST | Host database | 127.0.0.1 |
| DB_DATABASE | Nama database | smp_it_sis |
| DB_USERNAME | User database | sis_user |
| DB_PASSWORD | Password database | StrongPass123! |
| MAIL_MAILER | Driver email | smtp |
| MAIL_HOST | SMTP host | smtp.sekolah.sch.id |
| REDIS_HOST | Host Redis | 127.0.0.1 |
| SESSION_DRIVER | Driver session | redis |
| LOG_CHANNEL | Channel log | daily |

## Prosedur Deployment Antar Environment

1. Developer push ke branch `develop`.
2. CI/CD menjalankan test otomatis.
3. Jika lolos, merge ke `staging` untuk UAT.
4. Setelah UAT approved, merge ke `main`/`production`.
5. Deployment production dilakukan di luar jam aktif sekolah (malam hari).
