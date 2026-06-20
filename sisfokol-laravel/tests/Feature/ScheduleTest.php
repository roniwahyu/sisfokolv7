<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Day;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_schedule(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('admin');

        $academicYear = AcademicYear::factory()->create();
        $classroom = Classroom::factory()->create(['academic_year_id' => $academicYear->id]);
        $subject = Subject::factory()->create(['academic_year_id' => $academicYear->id]);
        $employee = Employee::factory()->create();
        $day = Day::factory()->create();
        $timeSlot = TimeSlot::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.schedules.store'), [
            'academic_year_id' => $academicYear->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'employee_id' => $employee->id,
            'day_id' => $day->id,
            'time_slot_id' => $timeSlot->id,
            'week_type' => 'all',
        ]);

        $response->assertRedirect(route('admin.schedules.index'));
        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
        ]);
    }
}
