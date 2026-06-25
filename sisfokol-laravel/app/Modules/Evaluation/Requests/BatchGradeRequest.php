<?php

namespace App\Modules\Evaluation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // [2026-06-25 | AI-Agent] Update to unified table names: classrooms -> kelas, students -> siswa
            'classroom_id' => 'required|exists:kelas,id',
            'subject_id' => 'required|exists:mapel,id',
            'type' => 'required|in:formative,summative',
            'assessment_id' => 'required|integer',
            'scores' => 'required|array',
            'scores.*.student_id' => 'required|exists:siswa,id',
            'scores.*.score' => 'required|numeric|min:0|max:100',
        ];
    }
}

