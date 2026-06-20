<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('academic_year')?->id;

        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('academic_years')->ignore($id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tahun Pelajaran',
            'start_date' => 'Tanggal Mulai',
            'end_date' => 'Tanggal Selesai',
        ];
    }
}
