<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'exists:schedules,id'],
            'date' => ['required', 'date'],
            'topic' => ['nullable', 'string'],
            'material' => ['nullable', 'string'],
            'student_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'schedule_id' => 'Jadwal',
            'date' => 'Tanggal',
            'topic' => 'Topik',
            'material' => 'Materi',
            'student_count' => 'Jumlah Siswa',
        ];
    }
}
