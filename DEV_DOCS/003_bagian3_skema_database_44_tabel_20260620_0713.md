# DEV_DOCS-003: Bagian 3 — Skema Database Fase 1 (48 Tabel)

- **Tanggal:** 2026-06-20 07:13
- **Topik:** Rincian skema database Bagian 3 (sudah dipresentasikan & disetujui user)
- **Terhubung ke ADR:** 007 (prinsip skema), 006 (RBAC), 004 (scope MVP)

---

## Prinsip skema (per ADR-007)

- InnoDB, utf8mb4_unicode_ci
- PK `BIGINT UNSIGNED AUTO_INCREMENT` (bukan MD5 varchar)
- FK + ON DELETE/UPDATE
- Standar kolom: timestamps + soft_deletes + created_by/updated_by
- Tipe sesuai domain (uang decimal(15,2), nilai tinyint, dst.)
- Semua tabel domain: tenant_id FK + index
- Helper migration: `$table->tenantAndAuditColumns()` untuk boilerplate

## Kuantitas per modul

| Modul | Tabel | Catatan |
|---|---:|---|
| Tenancy | 4 | tenants, branches, tenant_settings, subscriptions |
| Auth & RBAC | 9 | users, roles, permissions, 4 pivot Spatie, sessions, audit_logs |
| Academic | 11 | siswa, orang_tua, siswa_orang_tua, guru, tahun_ajaran, semester, kelas, kelas_siswa, mapel, mapel_jenis, jadwal |
| Evaluation | 7 | tp, lm, asesmen_formatif_nilai, asesmen_sumatif_nilai, raport_catatan, raport_sikap, raport_kenaikan |
| Finance | 5 | item_pembayaran, tagihan_siswa, pembayaran, pembayaran_rincian, tabungan_siswa |
| Presence | 3 | presensi, absensi, izin |
| Plugin infra | 2 | plugins, tenant_plugins |
| Plugin Kurikulum | 3 | kurikulum, struktur_kurikulum, komponen_kompetensi |
| RBAC Menu ACL | 2 | menus, menu_role_overrides (ditambahkan per ADR-010) |
| RBAC Field ACL | 2 | fields, field_role_overrides (ditambahkan per ADR-010) |
| **Total Fase 1** | **48** | (44 awal + 4 tabel ACL dari DEV_DOCS-005) |

## Rincian per modul

### Tenancy (4)
- `tenants`: id, nama, npsn UNIQUE, domain UNIQUE, jenjang, alamat, telepon, email, logo, aktif
- `branches`: id, tenant_id FK, nama, jenjang, alamat, aktif
- `tenant_settings`: id, tenant_id, key, value — UNIQUE(tenant_id, key). Mis. `tapel_aktif_id`, `smt_aktif`
- `subscriptions`: id, tenant_id, paket, mulai, berakhir, status (untuk SaaS billing Fase 2)

### Auth & RBAC (9) — lihat ADR-006
- `users`: id, tenant_id NULLABLE, branch_id NULLABLE, username, email, nama, password bcrypt, tipe, foto, aktif, last_login_at — UNIQUE(tenant_id, username)
- `roles`: id, name, team_id NULLABLE, guard_name, display_name, is_system
- `permissions`: id, name UNIQUE, guard_name, display_name, description, module, category
- `role_has_permissions`, `model_has_roles`, `model_has_permissions` (pivot Spatie)
- `sessions`: id, user_id NULLABLE, ip_address, user_agent, payload, last_activity
- `audit_logs`: id, tenant_id NULLABLE, user_id NULLABLE, event, model_type, model_id, old_values JSON, new_values JSON, ip_address, user_agent, created_at — IMMUTABLE

### Academic (11)
- `siswa`: id, tenant_id, nis, nisn, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, telepon, foto, agama, status(aktif/lulus/pindah/keluar), qrcode — UNIQUE(tenant_id, nis)
- `orang_tua`: id, tenant_id, nama, hubungan(ayah/ibu/wali), telepon, email, pekerjaan, alamat, username, password — UNIQUE(tenant_id, username) — **NORMALISASI** (vs passwordx_ortu di m_siswa)
- `siswa_orang_tua`: siswa_id, orang_tua_id — pivot (siswa bisa banyak wali)
- `guru`: id, tenant_id, nip, nama, jenis_kelamin, telepon, email, jabatan, foto, aktif — UNIQUE(tenant_id, nip)
- `tahun_ajaran`: id, tenant_id, nama("2026/2027"), tanggal_mulai, tanggal_selesai, aktif — UNIQUE(tenant_id, nama)
- `semester`: id, tenant_id, tahun_ajaran_id FK, nama(1|2), tanggal_mulai, tanggal_selesai, aktif
- `kelas`: id, tenant_id, branch_id NULLABLE, nama, tingkat, kapasitas
- `kelas_siswa`: id, tenant_id, kelas_id, siswa_id, tahun_ajaran_id, no_urut — UNIQUE(tenant_id, tahun_ajaran_id, kelas_id, siswa_id)
- `mapel`: id, tenant_id, kode, nama, jenjang, kkm decimal(5,2), kurikulum_id NULLABLE FK (plugin) — UNIQUE(tenant_id, kode)
- `mapel_jenis`: id, tenant_id, nama ("Wajib", "Muatan Lokal", "Peminatan")
- `jadwal`: id, tenant_id, tahun_ajaran_id, semester_id, kelas_id, mapel_id, guru_id, hari(1-7), jam_ke, jam_mulai time, jam_selesai time, ruang — UNIQUE(tenant_id, tahun_ajaran_id, semester_id, kelas_id, hari, jam_ke) — **NORMALISASI** (mapel_nama/jam jadi FK)

### Evaluation (7)
- `tp`: id, tenant_id, mapel_id, tahun_ajaran_id, kelas_id, kode, teks, urutan
- `lm`: id, tenant_id, mapel_id, tahun_ajaran_id, kelas_id, kode, teks, urutan
- `asesmen_formatif_nilai`: id, tenant_id, siswa_id, mapel_id, tp_id, tahun_ajaran_id, semester_id, nilai("Tercapai"/"Belum")
- `asesmen_sumatif_nilai`: id, tenant_id, siswa_id, mapel_id, lm_id NULLABLE, tahun_ajaran_id, semester_id, nilai_tes decimal, nilai_non_tes decimal, nilai_akhir decimal
- `raport_catatan`: id, tenant_id, siswa_id, tahun_ajaran_id, semester_id, isi
- `raport_sikap`: id, tenant_id, siswa_id, tahun_ajaran_id, semester_id, spiritual_predikat, spiritual_isi, sosial_predikat, sosial_isi
- `raport_kenaikan`: id, tenant_id, siswa_id, tahun_ajaran_id, status(Naik/Tinggal/Lulus), kelas_baru_id NULLABLE, tahun_ajaran_baru_id NULLABLE

### Finance (5)
- `item_pembayaran`: id, tenant_id, tahun_ajaran_id, semester_id NULLABLE, kelas_id NULLABLE, nama, jenis(spp/infaq/kegiatan/lainnya), nominal decimal(15,2), periode(bulanan/semester/tahunan/sekali), aktif — **NORMALISASI** (nominal varchar→decimal)
- `tagihan_siswa`: id, tenant_id, siswa_id, item_pembayaran_id, tahun_ajaran_id, bulan NULLABLE(SPP), nominal_tagihan decimal, nominal_bayar decimal, nominal_kurang decimal, lunas, tanggal_lunas — **NORMALISASI** (hapus snapshot siswa_*/item_*)
- `pembayaran`: id, tenant_id, siswa_id, no_nota, tanggal, total decimal, diterima_oleh FK→users — UNIQUE(tenant_id, no_nota)
- `pembayaran_rincian`: id, tenant_id, pembayaran_id FK(cascade), tagihan_siswa_id, jumlah decimal
- `tabungan_siswa`: id, tenant_id, siswa_id, no_rekening, saldo decimal — UNIQUE(tenant_id, no_rekening)

### Presence (3)
- `presensi`: id, tenant_id, user_id NULLABLE, siswa_id NULLABLE, tanggal, jenis(datang/pulang), jam time, telat_menit, metode(qr/manual)
- `absensi`: id, tenant_id, siswa_id NULLABLE, user_id NULLABLE, tanggal, jenis(sakit/ijin/alpha), keterangan
- `izin`: id, tenant_id, siswa_id NULLABLE, user_id NULLABLE, tanggal, jenis(masuk/pulang), jam NULLABLE, keterangan, status(pending/approved/rejected)

### Plugin Infrastructure (2)
- `plugins`: id, kode UNIQUE, nama, deskripsi, versi, is_core, provider_class, aktif_global — **global, bukan per-tenant**
- `tenant_plugins`: id, tenant_id, plugin_id, aktif, pengaturan JSON, diaktifkan_oleh FK→users, diaktifkan_pada — UNIQUE(tenant_id, plugin_id)

### Plugin Kurikulum — referensi (3) — dari skema yang user berikan
- `kurikulum`: id, tenant_id, kurikulum_id UNIQUE(K13/KURMER), nama_kurikulum, status_aktif
- `struktur_kurikulum`: id, tenant_id, kurikulum_id FK, jenjang, kelas, fase NULLABLE(A-F), jenis_kegiatan(intrakurikuler/kokurikuler_p5) — UNIQUE(tenant_id, kurikulum_id, jenjang, kelas, jenis_kegiatan)
- `komponen_kompetensi`: id, tenant_id, struktur_id FK, kode_kompetensi(KI-3/CP-001), teks_kompetensi, pendekatan_pedagogis(konvensional/deep_learning)

## Perbandingan normalisasi vs SISFOKOL

| Aspek | SISFOKOL lama | sisfokol_laravel baru |
|---|---|---|
| Engine | MyISAM | InnoDB |
| PK | varchar(50) MD5 | BIGINT AUTO_INCREMENT |
| FK | tidak ada | FK + ON DELETE/UPDATE |
| Nominal | varchar(15) | decimal(15,2) |
| Nilai | varchar(5) | decimal/tinyint |
| Snapshot siswa/item di transaksi | ya | FK ke master |
| Soft delete | tidak | deleted_at semua tabel |
| Audit | log_login/log_entri saja | audit_logs JSON + created_by/updated_by |
| Orang tua | passwordx_ortu di m_siswa | tabel orang_tua + pivot |
| Tahun ajaran aktif | hardcoded session | tenant_settings key-value |

## Status desain Bagian 3: ✅ FINAL & DISETUJUI USER

## Next
- ⏭️ Bagian 4: Plugin architecture (registry, hooks, menu, aktivasi tenant) — skema sudah mulai (plugins + tenant_plugins); ADR-009 akan catat kontrak plugin
