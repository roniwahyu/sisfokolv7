<?php

namespace App\Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan' => ['required', 'integer', 'between:1,12'],
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'item_pembayaran_id' => ['required', 'integer', 'exists:item_pembayaran,id'],
        ];
    }
}
