<?php

namespace App\Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BayarTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pembayaran' => ['required', 'array', 'min:1'],
            'pembayaran.*.tagihan_id' => ['required', 'integer', 'exists:tagihan_siswa,id'],
            'pembayaran.*.jumlah' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'pembayaran.required' => 'Pilih setidaknya satu tagihan untuk dibayar.',
            'pembayaran.*.jumlah.gt' => 'Nominal pembayaran harus lebih besar dari nol.',
        ];
    }
}
