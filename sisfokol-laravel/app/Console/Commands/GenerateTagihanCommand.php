<?php

namespace App\Console\Commands;

use App\Modules\Academic\Models\Kelas;
use App\Modules\Academic\Models\TahunAjaran;
use App\Modules\Finance\Models\ItemPembayaran;
use App\Modules\Finance\Services\TagihanGeneratorService;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Console\Command;

class GenerateTagihanCommand extends Command
{
    protected $signature = 'tagihan:generate {tenant_id?} {bulan?}';
    protected $description = 'Generate SPP tagihan untuk tenant (default: semua tenant, bulan ini)';

    public function handle(TagihanGeneratorService $svc): int
    {
        $tenantId = $this->argument('tenant_id');
        $bulan = $this->argument('bulan') ? (int) $this->argument('bulan') : (int) now()->format('n');

        $tenants = $tenantId 
            ? Tenant::where('id', $tenantId)->get() 
            : Tenant::where('aktif', true)->get();

        $total = 0;

        foreach ($tenants as $tenant) {
            $tapel = TahunAjaran::where('tenant_id', $tenant->id)->where('aktif', true)->first();
            if (! $tapel) {
                continue;
            }

            $items = ItemPembayaran::where('tenant_id', $tenant->id)
                ->where('aktif', true)
                ->where('jenis', 'spp')
                ->get();

            foreach ($items as $item) {
                $kelasList = Kelas::where('tenant_id', $tenant->id)->get();
                foreach ($kelasList as $kelas) {
                    $created = $svc->generateSpp($tapel, $kelas, $item, $bulan);
                    $total += $created;
                }
            }
            $this->info("Tenant {$tenant->nama}: tagihan bulan {$bulan} diproses.");
        }

        $this->info("Selesai. Total tagihan baru: {$total}");
        return 0;
    }
}
