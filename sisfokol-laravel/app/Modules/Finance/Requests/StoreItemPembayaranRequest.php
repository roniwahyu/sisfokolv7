<?php

namespace App\Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemPembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We use Spatie Policy authorization in controller
    }

    public function rules(): array
    {
        return [
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajaran,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'in:spp,kegiatan,infaq,lainnya'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'periode' => ['required', 'string', 'in:bulanan,sekali'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }
}
