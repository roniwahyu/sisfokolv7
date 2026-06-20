# DEV_DOCS-004: Bagian 4 — Arsitektur Plugin

- **Tanggal:** 2026-06-20 07:27
- **Topik:** Rincian desain Bagian 4 (sudah dipresentasikan & disetujui user)
- **Terhubung ke ADR:** 009 (plugin contract), 004 (scope MVP), 006 (RBAC)

---

## Filosofi

Plugin = modul yang bisa diaktifkan/nonaktifkan per tenant. Skema tabel plugin **selalu ada fisik** (di-migrate saat install); yang berubah hanya apakah tenant boleh mengaksesnya. Bukan "load PHP dinamis" — semua kode selalu di codebase.

## Struktur

```
app/Plugins/<Nama>/
  <Nama>Plugin.php              ← manifest (implement PluginContract)
  Providers/<Nama>ServiceProvider.php
  Models/  Controllers/  Requests/  Policies/
  Database/Migrations/
  routes.php
  Resources/views/
  menu.php                      ← deklarasi menu items (+ permission key)
  permissions.php               ← permission yang disumbang ke RBAC
```

## PluginContract (kontrak inti — lihat ADR-009)

```php
interface PluginContract {
    public function kode(): string;
    public function nama(): string;
    public function versi(): string;
    public function isCore(): bool;
    public function dependencies(): array;
    public function providerClass(): string;
    public function permissions(): array;
    public function menu(): array;
    public function boot(PluginContext $ctx): void;
}
```

## Plugin Registry (sumber kebenaran)

`ModuleServiceProvider` scan `app/Plugins/*/` saat boot:
1. Instantiate manifest class
2. Baca manifest (kode, nama, versi, deps, permissions, menu)
3. Daftarkan in-memory + sync ke tabel `plugins`
4. Register ServiceProvider + load routes.php

## Aktivasi per-tenant

- Tabel `tenant_plugins` kontrol (tenant_id, plugin_id, aktif)
- Admin → `admin/plugins/` toggle → emit `Plugin.Activated`/`Plugin.Deactivated` → permission seed → cache reset
- Nonaktifkan: menu hilang, route 403, **data tetap di DB** (aman, bisa re-aktifkan)

## Runtime: middleware `plugin:<Nama>`

```php
Route::middleware(['auth', 'plugin:Kurikulum'])->group(...);
```
Cek `tenant_plugins` untuk tenant aktif; SuperAdmin bypass.

## Menu renderer

Render dari core (selalu) + plugin aktif. **Filter per permission user** (lihat DEV_DOCS-005 untuk detail RBAC menu).

## Event hooks (loose coupling core↔plugin)

| Event core | Subscriber |
|---|---|
| `SiswaRegistered` | PelaporanOrtu (Fase 2) |
| `GradeSaved` | PelaporanOrtu notif ortu |
| `PaymentReceived` | PelaporanOrtu |
| `Evaluation.ResolveFramework` | **Kurikulum** jawab framework (TP/LM atau KI/KD) |
| `Raport.RenderSection` | Kurikulum + PendidikanKarakter |
| `Plugin.Activated/Deactivated` | semua plugin re-evaluate boot |

Coupling Evaluation↔Kurikulum: tanpa Kurikulum → generic numerik; dengan Kurikulum → framework K13/Kurmer via `struktur_kurikulum`.

### Detail resolusi framework Evaluasi↔Kurikulum (klarifikasi)

**Kurikulum AKTIF:**
- `mapel.kurikulum_id` FK → `kurikulum.id` menentukan framework aktif per mapel
- Event `Evaluation.ResolveFramework` di-fire saat controller Evaluation perlu render framework TP/LM
- Plugin Kurikulum listen → resolve dari `struktur_kurikulum` + `komponen_kompetensi` → inject metadata (KI-1/KI-2/KI-3/KI-4 atau CP) ke view
- Tabel `tp`/`lm` tetap generic (kode, teks, urutan) — **tidak ada FK langsung** ke tabel Kurikulum
- Link-nya via `mapel.kurikulum_id` → query `struktur_kurikulum` → template yang dipakai (konvensional vs deep learning)

**Kurikulum NON-AKTIF:**
- `mapel.kurikulum_id` = NULL
- Tabel `tp`/`lm` tetap dipakai sebagai container generic: guru input kode + teks TP/LM manual
- View menampilkan TP/LM tanpa framework metadata (no KI, no fase, no pendekatan pedagogis)
- Evaluasi formatif → "Tercapai/Belum" (tanpa konteks kompetensi)
- Evaluasi sumatif → nilai numerik saja (tanpa kategori CP)

## PluginContext (DI)

`boot(PluginContext $ctx)` dapat: tenant aktif, settings (JSON dari tenant_plugins), events dispatcher.

## Scaffold 8 plugin non-Kurikulum (Fase 1)

| Komponen | Scaffold? | Implementasi penuh? |
|---|---|---|
| Folder + manifest + ServiceProvider | ✅ | ✅ |
| Migration placeholder | ✅ | struktur dasar |
| permissions.php | ✅ (`<plugin>.view/.manage`) | ✅ |
| routes/controllers/views/menu | ❌ | Fase 2+ |

Tujuan: registry kenal, admin bisa aktivasi, tidak crash. Implementasi penuh = iterasi berikut (masing-masing ADR sendiri).

Daftar: Discipline, Inventory, Tahfidz, HafalanHadist, BimbinganKonseling, PendidikanKarakter, PelaporanOrtu, PWA (PWA beda sifat — frontend layer: service worker + manifest + offline route).

## Siklus hidup

```
Instalasi (dev) → registry discover → sync tabel plugins
Aktivasi (admin) → tenant_plugins → event → permission seed
Penggunaan (role) → middleware plugin: → menu render
Nonaktifkan (admin) → event → menu hilang, data tetap
Upgrade (dev) → versi manifest update → migration baru
```

## Security & isolasi plugin

- Plugin tidak akses langsung model core — via event atau PluginContext tenant-scoped
- Migration plugin wajib `tenant_id`
- Permission plugin otomatis scope tenant saat aktivasi
- Audit: aktivasi/nonaktifkan → audit_logs

## Status desain Bagian 4: ✅ FINAL & DISETUJUI USER
