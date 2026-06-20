<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('subject')?->id;
        $academicYearId = $this->input('academic_year_id');

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'subject_type_id' => ['nullable', 'exists:subject_types,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects')->where(fn ($query) => $query->where('academic_year_id', $academicYearId))->ignore($id),
            ],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'is_exam' => ['nullable', 'boolean'],
            'phase' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Kode Mapel',
            'name' => 'Nama Mapel',
            'phase' => 'Fase',
        ];
    }
}
