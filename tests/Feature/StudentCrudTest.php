<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_storing_a_student_can_reuse_nis_from_soft_deleted_student(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_GURU]);
        $trashedStudent = Student::query()->create([
            'nis' => '2624001',
            'name' => 'Siswa Lama',
            'class_name' => 'Paket C - XII',
            'status' => 'Aktif',
        ]);

        $trashedStudent->delete();

        $response = $this->actingAs($user)->post(route('students.store'), [
            'nis' => '2624001',
            'name' => 'Siswa Baru',
            'class_name' => 'Paket C - XII',
            'status' => 'Aktif',
        ]);

        $response
            ->assertRedirect(route('students.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', [
            'nis' => '2624001',
            'name' => 'Siswa Baru',
            'deleted_at' => null,
        ]);

        $this->assertStringContainsString(
            '__deleted_'.$trashedStudent->id,
            Student::withTrashed()->find($trashedStudent->id)->nis
        );
    }
}
