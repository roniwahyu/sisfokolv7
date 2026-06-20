<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExtracurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('extracurricular')?->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('extracurriculars')->ignore($id)],
            'name' => ['required', 'string', 'max:100'],
            'coach_id' => ['nullable', 'exists:employees,id'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Kode',
            'name' => 'Nama Ekstrakurikuler',
            'coach_id' => 'Pembina',
        ];
    }
}
