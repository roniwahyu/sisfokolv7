<?php

namespace App\Modules\Academic\Observers;

use App\Modules\Academic\Models\Siswa;
use App\Modules\Auth\Services\AuditLogger;

class SiswaObserver
{
    public function created(Siswa $siswa): void
    {
        app(AuditLogger::class)->log(
            'siswa.created',
            null,
            ['nis' => $siswa->nis, 'nama' => $siswa->nama],
            request(),
            [],
            Siswa::class,
            $siswa->id
        );
    }

    public function updated(Siswa $siswa): void
    {
        $changes = $siswa->getChanges();
        if (empty($changes)) {
            return;
        }

        app(AuditLogger::class)->log(
            'siswa.updated',
            null,
            $changes,
            request(),
            $siswa->getOriginal(),
            Siswa::class,
            $siswa->id
        );
    }

    public function deleted(Siswa $siswa): void
    {
        app(AuditLogger::class)->log(
            'siswa.deleted',
            null,
            ['nis' => $siswa->nis, 'nama' => $siswa->nama],
            request(),
            [],
            Siswa::class,
            $siswa->id
        );
    }
}
