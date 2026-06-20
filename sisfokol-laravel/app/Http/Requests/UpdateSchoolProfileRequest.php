<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'npsn' => ['nullable', 'string', 'max:50'],
            'nss' => ['nullable', 'string', 'max:50'],
            'headmaster_name' => ['nullable', 'string', 'max:200'],
            'headmaster_nip' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'google_map_url' => ['nullable', 'url'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nama Sekolah',
            'address' => 'Alamat',
            'city' => 'Kota',
            'phone' => 'Telepon',
            'headmaster_name' => 'Nama Kepala Sekolah',
        ];
    }
}
