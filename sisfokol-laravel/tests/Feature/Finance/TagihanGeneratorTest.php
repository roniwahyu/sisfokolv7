<?php

namespace Tests\Feature\Finance;

use App\Modules\Academic\Models\Kelas;
use App\Modules\Academic\Models\KelasSiswa;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Academic\Models\TahunAjaran;
use App\Modules\Finance\Models\ItemPembayaran;
use App\Modules\Finance\Models\TagihanSiswa;
use App\Modules\Finance\Services\TagihanGeneratorService;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagihanGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_spp_creates_tagihan_for_each_siswa_in_kelas(): void
    {
        [$tenant, $tapel, $kelas, $siswa1, $siswa2, $item] = $this->setupScenario();
        $svc = app(TagihanGeneratorService::class);

        $count = $svc->generateSpp($tapel, $kelas, $item, 7);

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('tagihan_siswa', [
            'siswa_id' => $siswa1->id,
            'item_pembayaran_id' => $item->id,
            'bulan' => 7,
            'nominal_tagihan' => 250000
        ]);
        $this->assertDatabaseHas('tagihan_siswa', [
            'siswa_id' => $siswa2->id,
            'item_pembayaran_id' => $item->id,
            'bulan' => 7,
            'nominal_tagihan' => 250000
        ]);
    }

    public function test_generate_spp_is_idempotent(): void
    {
        [$tenant, $tapel, $kelas, $siswa1, $siswa2, $item] = $this->setupScenario();
        $svc = app(TagihanGeneratorService::class);

        $svc->generateSpp($tapel, $kelas, $item, 7);
        $count = $svc->generateSpp($tapel, $kelas, $item, 7); // Run again

        $this->assertSame(0, $count); // 0 new tagihan created
        $this->assertSame(2, TagihanSiswa::count()); // Count remains 2
    }

    public function test_generate_skips_already_lunas(): void
    {
        [$tenant, $tapel, $kelas, $siswa1, $siswa2, $item] = $this->setupScenario();
        $svc = app(TagihanGeneratorService::class);

        // Pre-create a paid tagihan for siswa1
        TagihanSiswa::create([
            'tenant_id' => $tenant->id,
            'siswa_id' => $siswa1->id,
            'item_pembayaran_id' => $item->id,
            'tahun_ajaran_id' => $tapel->id,
            'bulan' => 7,
            'nominal_tagihan' => 250000,
            'nominal_bayar' => 250000,
            'nominal_kurang' => 0,
            'lunas' => true,
            'tanggal_lunas' => now()
        ]);

        $count = $svc->generateSpp($tapel, $kelas, $item, 7);
        
        $this->assertSame(1, $count); // Only siswa2 gets generated
        $this->assertSame(2, TagihanSiswa::count()); // Total remains 2 (1 pre-created, 1 newly generated)
    }

    private function setupScenario(): array
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set($tenant->id);

        $tapel = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'aktif' => true,
            'tenant_id' => $tenant->id
        ]);

        $kelas = Kelas::create([
            'nama' => '7-A',
            'tingkat' => 7,
            'kapasitas' => 32,
            'tenant_id' => $tenant->id
        ]);

        $siswa1 = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        $siswa2 = Siswa::factory()->create(['tenant_id' => $tenant->id]);

        KelasSiswa::create([
            'siswa_id' => $siswa1->id,
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $tapel->id,
            'tenant_id' => $tenant->id,
            'no_urut' => 1,
        ]);
        
        KelasSiswa::create([
            'siswa_id' => $siswa2->id,
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $tapel->id,
            'tenant_id' => $tenant->id,
            'no_urut' => 2,
        ]);

        $item = ItemPembayaran::create([
            'tahun_ajaran_id' => $tapel->id,
            'nama' => 'SPP',
            'jenis' => 'spp',
            'nominal' => 250000,
            'periode' => 'bulanan',
            'tenant_id' => $tenant->id
        ]);

        return [$tenant, $tapel, $kelas, $siswa1, $siswa2, $item];
    }
}
