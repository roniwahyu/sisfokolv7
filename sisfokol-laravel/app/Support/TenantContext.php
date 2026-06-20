<?php

namespace App\Support;

/**
 * ADR-003: Singleton menyimpan tenant aktif untuk request ini.
 * Di-set oleh ResolveTenant middleware dari Auth::user()->tenant_id.
 * SuperAdmin (tenant_id NULL) → isSuperAdminContext() = true (tembus semua tenant).
 */
class TenantContext
{
    private ?int $tenantId = null;
    private ?int $branchId = null;
    private array $settings = [];

    public function set(int $tenantId, ?int $branchId = null, array $settings = []): void
    {
        $this->tenantId = $tenantId;
        $this->branchId = $branchId;
        $this->settings = $settings;
    }

    public function clear(): void
    {
        $this->tenantId = null;
        $this->branchId = null;
        $this->settings = [];
    }

    public function isInitialized(): bool
    {
        return $this->tenantId !== null;
    }

    public function isSuperAdminContext(): bool
    {
        return ! $this->isInitialized();
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'id'        => $this->tenantId,
            'branchId'  => $this->branchId,
            'settings'  => $this->settings,
            default     => $this->settings[$name] ?? null,
        };
    }
}
