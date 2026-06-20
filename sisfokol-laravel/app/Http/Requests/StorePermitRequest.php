<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'type' => ['required', 'in:in,out'],
            'time' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string'],
            'attachment_path' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'NIS/NIP',
            'date' => 'Tanggal',
            'type' => 'Jenis Izin',
            'time' => 'Jam',
            'reason' => 'Alasan',
        ];
    }
}
