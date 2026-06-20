<?php

namespace Tests\Unit\Support;

use App\Support\TenantContext;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    public function test_initial_state_is_uninitialized(): void
    {
        $ctx = new TenantContext();
        $this->assertNull($ctx->id);
        $this->assertFalse($ctx->isInitialized());
    }

    public function test_set_and_get_tenant_id(): void
    {
        $ctx = new TenantContext();
        $ctx->set(tenantId: 1, branchId: 2);

        $this->assertTrue($ctx->isInitialized());
        $this->assertSame(1, $ctx->id);
        $this->assertSame(2, $ctx->branchId);
    }

    public function test_clear_resets_state(): void
    {
        $ctx = new TenantContext();
        $ctx->set(tenantId: 1, branchId: null);
        $ctx->clear();

        $this->assertFalse($ctx->isInitialized());
        $this->assertNull($ctx->id);
    }

    public function test_is_superadmin_context_when_uninitialized(): void
    {
        $ctx = new TenantContext();
        $this->assertTrue($ctx->isSuperAdminContext());
    }

    public function test_is_not_superadmin_context_when_initialized(): void
    {
        $ctx = new TenantContext();
        $ctx->set(tenantId: 1);
        $this->assertFalse($ctx->isSuperAdminContext());
    }
}
