<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('schedule')?->id;

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'day_id' => ['required', 'exists:days,id'],
            'time_slot_id' => ['required', 'exists:time_slots,id'],
            'week_type' => ['required', 'in:all,odd,even'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'classroom_id' => 'Kelas',
            'subject_id' => 'Mapel',
            'employee_id' => 'Guru',
            'room_id' => 'Ruang',
            'day_id' => 'Hari',
            'time_slot_id' => 'Jam',
            'week_type' => 'Minggu',
        ];
    }
}
