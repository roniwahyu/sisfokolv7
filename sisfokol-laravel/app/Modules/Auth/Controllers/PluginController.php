<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\PluginActivationService;
use App\Plugins\Infrastructure\Models\{Plugin, TenantPlugin};
use App\Support\PluginRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PluginController extends Controller
{
    public function __construct(
        private PluginActivationService $activation,
        private PluginRegistry $registry,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('plugin.activate');
        $tenantId = $request->user()->tenant_id;
        $plugins = Plugin::orderBy('nama')->get();
        $activeMap = [];
        if ($tenantId) {
            $activeMap = TenantPlugin::where('tenant_id', $tenantId)
                ->where('aktif', true)->pluck('plugin_id', 'plugin_id')->all();
        }
        return view('plugins.index', compact('plugins', 'activeMap'));
    }

    public function activate(Request $request, string $kode)
    {
        Gate::authorize('plugin.activate');
        $user = $request->user();
        if (! $user->tenant_id) abort(403, 'SuperAdmin tidak mengaktifkan plugin per-tenant.');
        $this->activation->activate($user->tenant_id, $kode, $user->id);
        return back()->with('status', "Plugin '{$kode}' diaktifkan.");
    }

    public function deactivate(Request $request, string $kode)
    {
        Gate::authorize('plugin.activate');
        $user = $request->user();
        if (! $user->tenant_id) abort(403, 'SuperAdmin tidak menonaktifkan plugin per-tenant.');
        $this->activation->deactivate($user->tenant_id, $kode);
        return back()->with('status', "Plugin '{$kode}' dinonaktifkan. Data tetap aman.");
    }
}
