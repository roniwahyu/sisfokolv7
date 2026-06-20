# ADR-009: Plugin Contract — Plug-and-Play per Tenant

- **Tanggal:** 2026-06-20 07:27
- **Status:** Diterima (Accepted)

## Konteks

Fase 1 butuh 9 plugin yang bisa diaktifkan per tenant (Kurikulum, Discipline, Inventory, Tahfidz, HafalanHadist, BimbinganKonseling, PendidikanKarakter, PelaporanOrtu, PWA). Tanpa kontrak seragam, tiap plugin akan punya cara berbeda untuk register menu, permission, route, event → tidak maintainable.

## Keputusan

Setiap plugin wajib implement `PluginContract`:

```php
interface PluginContract {
    public function kode(): string;          // unik, "kurikulum"
    public function nama(): string;
    public function versi(): string;
    public function isCore(): bool;          // false
    public function dependencies(): array;   // kode plugin lain yang required
    public function providerClass(): string;
    public function permissions(): array;    // RBAC yang disumbang
    public function menu(): array;           // item menu + permission key
    public function boot(PluginContext $ctx): void;
}
```

### Manifest & registry
- Setiap plugin punya class manifest `<Nama>Plugin.php` di root folder pluginnya
- `ModuleServiceProvider` scan `app/Plugins/*/` saat booting → instantiate → simpan ke `PluginRegistry` → sync ke tabel `plugins`
- Registry cache di production

### Aktivasi per-tenant via `tenant_plugins`
- ON: emit `Plugin.Activated` → seed permission → cache reset → plugin boot listener
- OFF: emit `Plugin.Deactivated` → menu hilang, route 403 via middleware `plugin:<Kode>`; **data tetap** (tidak dihapus)

### Akses runtime
- Middleware `plugin:<Kode>` cek `(tenant_id, plugin_id, aktif=1)`; SuperAdmin bypass
- Menu renderer hanya tampilkan menu plugin yang aktif **dan** user punya permission

### Event hooks (loose coupling)
- Core emit: `SiswaRegistered`, `GradeSaved`, `PaymentReceived`, `Evaluation.ResolveFramework`, `Raport.RenderSection`, `Plugin.Activated/Deactivated`
- Plugin subscribe via `boot(PluginContext)` — tidak boleh akses langsung model core, harus via event/PluginContext tenant-scoped

### Scaffold (Fase 1, 8 plugin non-Kurikulum)
- Wajib: folder + manifest + ServiceProvider + migration placeholder + permissions.php
- Boleh kosong (Fase 2+): routes/controllers/views/menu/event listener
- Tujuan: registry kenal, admin bisa aktivasi, tidak crash saat aktif

## Konsekuensi

- ✅ Pola seragam → plugin baru mudah ditambah (cukup ikut kontrak)
- ✅ Tidak ada "hard delete" data saat nonaktif → aman re-aktifkan
- ✅ Loose coupling core↔plugin via event
- ❌ Overhead boilerplate per plugin → dimitigasi stub generator (`php artisan make:plugin`)
- ⚠️ Registry scan saat boot butuh cache di production
