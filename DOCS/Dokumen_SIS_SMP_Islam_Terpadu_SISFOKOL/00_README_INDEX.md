# Dokumen Proyek — Sistem Informasi Sekolah (SIS) SMP Islam Terpadu

> **Kode Proyek:** SIS-SMPIT-2026
> **Platform Dasar:** SISFOKOL v7.00 (Code: SmartOffice) — PHP Native 8.2.4 + MySQL/MariaDB
> **Versi Dokumen:** 1.0 (Final Draft)
> **Tahun Anggaran:** 2026
> **Pemilik Dokumen:** Kepala Sekolah & Tim IT SMP Islam Terpadu

---

## 1. Tentang Paket Dokumen Ini

Paket dokumen ini disusun mengikuti standar rekayasa perangkat lunak (Software Engineering) berdasarkan analisis mendalam terhadap basis kode **SISFOKOL v7.00 (Code: SmartOffice)** yang dikembangkan oleh Agus Muhajir, S.Kom. Dokumen ini menyesuaikan platform tersebut ke dalam konteks **SMP Islam Terpadu** dengan mempertimbangkan kebutuhan spesifik sekolah Islam (integrasi nilai tahfidz/keagamaan, kegiatan keislaman, serta keterlibatan orang tua).

## 2. Konvensi & Cara Membaca

- **Diagram** disajikan dalam sintaks **Mermaid** (didukung GitHub, GitLab, VS Code, dan sebagian besar penampil Markdown modern) dan dilengkapi deskripsi naratif.
- **Tabel** menggunakan Markdown standar.
- **Kode modul/folder** mengacu langsung pada struktur repository SISFOKOL (`adm`, `admks`, `admwk`, `admgr`, `admbk`, `admbdh`, `admpiket`, `admsw`, `adminv`).
- **Nama tabel database** mengacu langsung pada berkas `db/sisfokol_v7.sql` (75 tabel).

## 3. Daftar Lengkap Dokumen (31 Dokumen)

### FASE A — Inisiasi & Perencanaan Proyek
| No | Dokumen | Diagram / Tabel Utama |
|----|---------|----------------------|
| 01 | Project Charter | Tabel Stakeholder, Tabel Estimasi Waktu & Anggaran |
| 02 | Visi, Tujuan, dan Ruang Lingkup | Tabel Modul Fase 1 vs Fase 2 |
| 03 | Identifikasi Stakeholder | Tabel Stakeholder + Kebutuhan |
| 04 | Analisis Kebutuhan Bisnis | Tabel Masalah Bisnis + Dampak |
| 05 | Software Requirements Specification (SRS) | Tabel Kebutuhan Fungsional & Non-Fungsional |
| 06 | User Role dan Hak Akses (RBAC) | Matriks Role vs Hak Akses |
| 07 | Proses Bisnis (As-Is & To-Be) | BPMN/Flowchart minimal 5 proses |
| 08 | Use Case / User Story | Use Case Diagram + daftar use case |
| 09 | Product Backlog | Tabel Product Backlog + Prioritas |
| 10 | Studi Kelayakan | Tabel Penilaian Kelayakan (5 aspek) |

### FASE B — Desain Awal
| No | Dokumen | Diagram / Tabel Utama |
|----|---------|----------------------|
| 11 | Arsitektur Sistem | Diagram Arsitektur 3-Tier |
| 12 | Desain Database | ERD + minimal 10 tabel |

### FASE C — Desain Detail Sistem
| No | Dokumen | Diagram / Tabel Utama |
|----|---------|----------------------|
| 13 | Data Dictionary | Kamus Data minimal 30 field |
| 14 | UML Lengkap | Use Case, Activity (4), Sequence (4), Class Diagram |
| 15 | Desain Antarmuka (UI/UX) | Wireframe minimal 10 halaman |
| 16 | Desain Laporan | Layout 5–6 jenis laporan |

### FASE D — Pengembangan Teknis
| No | Dokumen | Diagram / Tabel Utama |
|----|---------|----------------------|
| 17 | Spesifikasi Teknologi | Tabel Teknologi |
| 18 | Struktur Kode & Coding Standard | Diagram Struktur Folder |
| 19 | Konfigurasi Environment | Tabel Environment (Dev/Staging/Prod) |
| 20 | Keamanan Sistem | Tabel Mekanisme Keamanan |

### FASE E — Pengujian
| No | Dokumen | Diagram / Tabel Utama |
|----|---------|----------------------|
| 21 | Test Plan | Tabel Jenis Pengujian & Strategi |
| 22 | Test Case & Scenario | Tabel Test Case (minimal 30) |
| 23 | Hasil Pengujian & Bug List | Tabel Bug Tracker |
| 24 | User Acceptance Test (UAT) | Form UAT + Tabel Hasil |

### FASE F — Implementasi & Deployment
| No | Dokumen | Diagram / Tabel Utama |
|----|---------|----------------------|
| 25 | Deployment Plan | Timeline / Gantt Chart |
| 26 | Migrasi Data | Tabel Data Dimigrasikan + Strategi |
| 27 | SOP Operasional Sistem | Flowchart SOP harian |
| 28 | Backup dan Recovery | Tabel Jadwal Backup & Prosedur Restore |

### FASE G — Dokumentasi Pengguna & Pemeliharaan
| No | Dokumen | Diagram / Tabel Utama |
|----|---------|----------------------|
| 29 | User Manual | Panduan per Role |
| 30 | Maintenance Plan | Tabel Jadwal Maintenance & SLA |
| 31 | Release Note & Change Log | Tabel Riwayat Versi |

## 4. Tim Penyusun

| Peran | Nama | Tanggung Jawab |
|-------|------|----------------|
| Executive Sponsor | Kepala Sekolah | Persetujuan & anggaran |
| Project Manager | Wakil Kepala Bidang Kurikulum | Koordinasi lintas tim |
| Business Analyst | Tim IT / Vendor | Dokumen kebutuhan & SRS |
| Lead Developer | Programmer | Implementasi & integrasi |
| QA Lead | Tim IT | Pengujian & UAT |
| DBA / SysAdmin | Tim IT | Database, server, deployment |

## 5. Lisensi & Atribusi

Aplikasi dasar **SISFOKOL v7.00** bersifat *open source* (sumber: `gitlab.com/hajirodeon/sisfokol-v7.00-code-smartoffice`, penulis: Agus Muhajir, S.Kom). Dokumen ini disusun untuk keperluan internal proyek implementasi di SMP Islam Terpadu.
