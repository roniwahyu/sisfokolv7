<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SchoolProfileSeeder;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\DaySeeder;
use Database\Seeders\HourSeeder;
use Database\Seeders\TimeSlotSeeder;
use Database\Seeders\SubjectTypeSeeder;
use Database\Seeders\AttendanceTimeSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ClassroomSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\FieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeededUsersLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_superadmin_can_login(): void
    {
        $response = $this->post('/login', [
            'username' => 'superadmin',
            'password' => 'SuperAdmin#2026',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_admin_sekolah_can_login(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_tenant_admin_can_login(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin.sekolah',
            'password' => 'demo1234',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_piket_demo_can_login(): void
    {
        $response = $this->post('/login', [
            'username' => 'piket.demo',
            'password' => 'demo1234',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_bk_demo_can_login(): void
    {
        $response = $this->post('/login', [
            'username' => 'bk.demo',
            'password' => 'demo1234',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_guru_demo_can_login(): void
    {
        $response = $this->post('/login', [
            'username' => 'guru.demo',
            'password' => 'demo1234',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_walikelas_demo_can_login(): void
    {
        $response = $this->post('/login', [
            'username' => 'walikelas.demo',
            'password' => 'demo1234',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_siswa_demo_can_login(): void
    {
        $response = $this->post('/login', [
            'username' => 'siswa.2024001',
            'password' => 'demo1234',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}
