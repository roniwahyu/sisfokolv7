<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Services\ImpersonationService;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(private ImpersonationService $impersonation) {}

    public function start(User $target, Request $request)
    {
        // 404 if feature disabled entirely (defense in depth)
        if (! config('impersonate.enabled', false)) abort(404);

        $impersonator = $request->user();
        if (! $this->impersonation->canStart($impersonator, $target)) {
            abort(403, 'Anda tidak dapat melakukan impersonation ke user ini.');
        }

        $this->impersonation->start($impersonator, $target, $request);
        return redirect()->route('dashboard')->with('status', "Login sebagai {$target->nama}");
    }

    public function stop(Request $request)
    {
        $this->impersonation->stop($request);
        return redirect()->route('dashboard')->with('status', 'Kembali ke akun Anda.');
    }
}
