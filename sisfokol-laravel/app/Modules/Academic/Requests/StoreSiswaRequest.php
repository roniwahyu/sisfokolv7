<?php

namespace App\Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('student.*');
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'nis' => [
                'required',
                'string',
                'max:30',
                Rule::unique('siswa')->where('tenant_id', $tenantId),
            ],
            'nisn' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('siswa')->where('tenant_id', $tenantId),
            ],
            'nama' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:50'],
            'tanggal_lahir' => ['nullable', 'date'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'agama' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:aktif,nonaktif,lulus,pindah,keluar'],
        ];
    }
}
