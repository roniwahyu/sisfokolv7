<?php

namespace Tests\Unit\Models\Traits;

use App\Models\Traits\BelongsToTenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class BelongsToTenantTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Minimal table for the trait test
        \Schema::create('stub_domain', function ($t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        \Schema::dropIfExists('stub_domain');
        parent::tearDown();
    }

    public function test_global_scope_filters_by_tenant_id(): void
    {
        app(TenantContext::class)->set(tenantId: 1);

        StubModel::insert([
            ['id' => 1, 'tenant_id' => 1, 'name' => 'A', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'tenant_id' => 2, 'name' => 'B', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = StubModel::all();
        $this->assertCount(1, $results);
        $this->assertSame('A', $results->first()->name);
    }

    public function test_superadmin_context_sees_all_tenants(): void
    {
        // No set() → superadmin context
        StubModel::insert([
            ['id' => 1, 'tenant_id' => 1, 'name' => 'A', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'tenant_id' => 2, 'name' => 'B', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertCount(2, StubModel::all());
    }

    public function test_create_auto_fills_tenant_id(): void
    {
        app(TenantContext::class)->set(tenantId: 5);
        $model = StubModel::create(['name' => 'X']);
        $this->assertSame(5, $model->tenant_id);
    }
}

class StubModel extends Model
{
    use BelongsToTenant;
    protected $table = 'stub_domain';
    protected $fillable = ['name', 'tenant_id'];
    public $timestamps = true;
}
