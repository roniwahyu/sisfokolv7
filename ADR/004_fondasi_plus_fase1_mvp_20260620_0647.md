# ADR-004: Scope MVP Fase 1 (Fondasi + 6 Core Module + Plugin Infra + 1 Plugin Referensi)

- **Tanggal:** 2026-06-20 06:47
- **Status:** Diterima (Accepted)

## Konteks

Scope penuh SISFOKOL = 75 tabel, 8+ domain, estimasi 26–30 minggu (per dokumen analisis). Tidak realistis dikerjakan dalam satu siklus. Perlu didekomposisi.

Pengguna memilih scope: **Fondasi + Fase 1 MVP** (bukan seluruh domain sekaligus, bukan hanya fondasi).

## Keputusan

**Core modules (selalu aktif, fully functional di Fase 1):**

| Modul | Cakupan |
|---|---|
| `Tenancy` | Tenant, Branch, profil sekolah |
| `Auth` | User, Role, Permission, login, RBAC, audit log, impersonation |
| `Academic` | Siswa, Guru, Kelas, TahunAjaran, Semester, Mapel, Jadwal |
| `Evaluation` | TP/LM, Asesmen formatif/sumatif, Rapor |
| `Finance` | ItemPembayaran, SPP, Tagihan, Pembayaran, Tabungan |
| `Presence` | Presensi (QR), Absensi, Izin |

**Plugin infrastructure (fully working):**
- Registry plugin, aktivasi per-tenant (`tenant_plugins`), middleware `EnsurePluginEnabled`, menu renderer cek plugin aktif, event hooks

**1 plugin referensi dibangun penuh:** `Kurikulum` (mesin nilai generik K13/Kurmer/Muatan Lokal/Deep Learning)

**8 plugin lain:** scaffold saja (migrasi + registrasi registry, tanpa UI):
Discipline, Inventory, Tahfidz, Hafalan Hadist, Bimbingan Konseling, Pendidikan Karakter, Pelaporan Ortu, PWA

**ETL data master** dari `sisfokol_v7` (siswa, guru, kelas, mapel, tapel) → skema baru.

## Konsekuensi

- ✅ MVP yang benar-benar berjalan & teruji dalam waktu wajar
- ✅ Membuktikan pola plugin (Kurikulum) sebelum bangun 8 plugin lain
- ✅ Plugin infrastructure siap; plugin lain tinggal "diisi" di iterasi berikut
- ❌ 8 plugin belum berfungsi → dimitigasi dengan scaffold yang jelas & ADR per plugin
- 🔄 Plugin non-core dibangun satu per satu di Fase 2+, masing-masing dengan ADR-nya sendiri
