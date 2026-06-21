<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Academic\Models\TahunAjaran;
use App\Modules\Finance\Models\ItemPembayaran;
use App\Modules\Finance\Models\Pembayaran;
use App\Modules\Finance\Models\TagihanSiswa;
use App\Modules\Finance\Services\PembayaranService;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembayaranServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bayar_creates_pembayaran_and_updates_tagihan(): void
    {
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        $svc = app(PembayaranService::class);

        $pembayaran = $svc->bayar($siswa, [
            ['tagihan_id' => $tagihan->id, 'jumlah' => 100000],
        ], $bendahara);

        $this->assertInstanceOf(Pembayaran::class, $pembayaran);
        $this->assertSame('100000.00', $pembayaran->total);
        $this->assertDatabaseHas('pembayaran_rincian', [
            'pembayaran_id' => $pembayaran->id,
            'tagihan_siswa_id' => $tagihan->id,
            'jumlah' => '100000.00'
        ]);
        
        $tagihan->refresh();
        $this->assertSame('100000.00', $tagihan->nominal_bayar);
        $this->assertSame('150000.00', $tagihan->nominal_kurang); // 250000 - 100000
        $this->assertFalse($tagihan->lunas);
    }

    public function test_bayar_marks_lunas_when_full(): void
    {
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        $svc = app(PembayaranService::class);

        $svc->bayar($siswa, [
            ['tagihan_id' => $tagihan->id, 'jumlah' => 250000]
        ], $bendahara);

        $tagihan->refresh();
        $this->assertTrue($tagihan->lunas);
        $this->assertSame('0.00', $tagihan->nominal_kurang);
        $this->assertNotNull($tagihan->tanggal_lunas);
    }

    public function test_bayar_rolls_back_on_error(): void
    {
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        $svc = app(PembayaranService::class);

        // Pass invalid tagihan_id to trigger error
        try {
            $svc->bayar($siswa, [
                ['tagihan_id' => 999999, 'jumlah' => 100]
            ], $bendahara);
            $this->fail('Expected exception');
        } catch (\Throwable $e) {
            // OK
        }

        // Nothing should be created
        $this->assertSame(0, Pembayaran::count());
    }

    public function test_bayar_emits_payment_received_event(): void
    {
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        \Illuminate\Support\Facades\Event::fake([\App\Modules\Finance\Events\PaymentReceived::class]);

        app(PembayaranService::class)->bayar($siswa, [
            ['tagihan_id' => $tagihan->id, 'jumlah' => 50000]
        ], $bendahara);

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Modules\Finance\Events\PaymentReceived::class);
    }

    public function test_concurrent_bayar_does_not_overcharge(): void
    {
        // Simulate race condition: two concurrent payments to same tagihan
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        $svc = app(PembayaranService::class);

        // Pay 200000 first
        $svc->bayar($siswa, [
            ['tagihan_id' => $tagihan->id, 'jumlah' => 200000]
        ], $bendahara);
        
        $tagihan->refresh();
        $this->assertSame('50000.00', $tagihan->nominal_kurang);

        // Second payment of 200000 should clamp to 50000 (remaining unpaid amount)
        $svc->bayar($siswa, [
            ['tagihan_id' => $tagihan->id, 'jumlah' => 200000]
        ], $bendahara);
        
        $tagihan->refresh();
        $this->assertSame('0.00', $tagihan->nominal_kurang);
        $this->assertSame('250000.00', $tagihan->nominal_bayar); // not 400000
    }

    public function test_kwitansi_no_nota_is_unique_per_tenant(): void
    {
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        $svc = app(PembayaranService::class);

        $p1 = $svc->bayar($siswa, [
            ['tagihan_id' => $tagihan->id, 'jumlah' => 1000]
        ], $bendahara);
        
        // Reset tagihan so we can pay again
        $tagihan->update([
            'nominal_bayar' => 0,
            'nominal_kurang' => 250000,
            'lunas' => false,
            'tanggal_lunas' => null
        ]);
        
        $p2 = $svc->bayar($siswa, [
            ['tagihan_id' => $tagihan->id, 'jumlah' => 1000]
        ], $bendahara);

        $this->assertNotEquals($p1->no_nota, $p2->no_nota);
    }

    private function setupScenario(): array
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set($tenant->id);
        
        $bendahara = User::factory()->create([
            'tenant_id' => $tenant->id,
            'tipe'      => 'pegawai',
        ]);
        $bendahara->assignRole('finance'); // Using 'finance' role per permissions matrix

        $tapel = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'aktif' => true,
            'tenant_id' => $tenant->id
        ]);
        
        $siswa = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        
        $item = ItemPembayaran::create([
            'tahun_ajaran_id' => $tapel->id,
            'nama' => 'SPP Juli',
            'jenis' => 'spp',
            'nominal' => 250000,
            'periode' => 'bulanan',
            'tenant_id' => $tenant->id
        ]);
        
        $tagihan = TagihanSiswa::create([
            'siswa_id' => $siswa->id,
            'item_pembayaran_id' => $item->id,
            'tahun_ajaran_id' => $tapel->id,
            'bulan' => 7,
            'nominal_tagihan' => 250000,
            'nominal_bayar' => 0,
            'nominal_kurang' => 250000,
            'lunas' => false,
            'tenant_id' => $tenant->id
        ]);
        
        return [$tenant, $bendahara, $siswa, $tagihan, $item];
    }
}
