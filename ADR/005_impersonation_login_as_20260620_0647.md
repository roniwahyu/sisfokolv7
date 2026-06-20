# ADR-005: Fitur "Login As" (Impersonation) — Hierarkis & Env-Gated

- **Tanggal:** 2026-06-20 06:47
- **Status:** Diterima (Accepted)

## Konteks

Untuk onboarding, setup awal, dan troubleshooting, SuperAdmin & Admin Sekolah perlu bisa menjalankan sistem **sebagai** role/user di bawahnya — tanpa mengetahui password user target. Namun fitur ini berisiko disalahgunakan bila selalu aktif di production.

## Keputusan

Implementasi **impersonation hierarkis** dengan package `lab404/laravel-impersonate`.

### Hierarki (hanya ke bawah)
- **SuperAdmin** → dapat impersonate Admin Sekolah (semua tenant) + semua role fungsional
- **Admin Sekolah** → dapat impersonate semua role fungsional di tenant-nya sendiri
- **Role fungsional** → **tidak bisa** impersonate siapa pun

### Kontrol & keamanan
1. **Env-gated:** `.env` → `IMPERSONATION_ENABLED=true/false`. Default **false** di production.
   - Saat `false`: tombol "Login As" di-hide, route 404, middleware menolak (defense in depth).
2. **Original identity** disimpan di session key `impersonated_by`; tombol "Return to my account" selalu tersedia.
3. **Banner merah persistent** di setiap halaman saat impersonate, supaya admin tidak lupa.
4. **Audit trail wajib:** event `impersonate.start` & `impersonate.stop` ke `audit_logs` (immutable) — siapa, target, kapan, IP.
5. **Aksi sensitif diblokir** saat impersonate via middleware `BlockWhileImpersonating`:
   - Ubah credential user target
   - Ubah role/permission assignment
   - Aktifkan/nonaktifkan plugin
6. **Validasi target** via `CanImpersonate` middleware: fitur aktif + target di bawah hierarki + target ≠ diri + target aktif + di scope tenant.

## Konsekuensi

- ✅ Onboarding & troubleshooting tanpa perlu reset password user
- ✅ Verifikasi alur "dari kacamata role lain" untuk QA
- ✅ Default-off di production → minim risiko
- ⚠️ Bila lupa di-enable di production → dimitigasi banner + audit log + blokir aksi sensitif
- ⚠️ Kompleksitas tambahan pada middleware chain → didokumentasikan di ADR-007 (middleware)
