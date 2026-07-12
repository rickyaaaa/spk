<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_download_student_template(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_GURU]);

        $response = $this->actingAs($admin)->get(route('students.template'));

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));

        // Verify separator declaration is present
        $this->assertEquals('sep=,', trim($lines[0]));
        // Verify header
        $this->assertEquals('nis,nama,kelas,status', trim($lines[1]));
        // Verify example data
        $this->assertEquals('2624007,Dayu,"Paket C - XII",Aktif', trim($lines[2]));
    }

    public function test_can_import_students_successfully_with_commas(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_GURU]);

        // Let's create an existing student to verify update functionality
        $existing = Student::query()->create([
            'nis' => '2624001',
            'name' => 'Old Name',
            'class_name' => 'Paket C - XII',
            'status' => 'Aktif',
        ]);

        $csvContent = "sep=,\nnis,nama,kelas,status\n2624001,Updated Name,Paket C - XII,Aktif\n2624007,Dayu,Paket C - XII,Aktif\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('students.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');

        // Verify existing student was updated
        $this->assertDatabaseHas('students', [
            'nis' => '2624001',
            'name' => 'Updated Name',
        ]);

        // Verify new student was created
        $this->assertDatabaseHas('students', [
            'nis' => '2624007',
            'name' => 'Dayu',
            'class_name' => 'Paket C - XII',
            'status' => 'Aktif',
        ]);
    }

    public function test_can_import_students_successfully_with_semicolons(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_GURU]);

        $csvContent = "nis;nama;kelas;status\n2624008;Dani;Paket C - XII;Evaluasi\n";
        $file = UploadedFile::fake()->createWithContent('students_semicolon.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('students.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'nis' => '2624008',
            'name' => 'Dani',
            'class_name' => 'Paket C - XII',
            'status' => 'Evaluasi',
        ]);
    }

    public function test_import_can_reuse_nis_from_soft_deleted_student(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_GURU]);
        $trashedStudent = Student::query()->create([
            'nis' => '2624010',
            'name' => 'Siswa Lama',
            'class_name' => 'Paket C - XI',
            'status' => 'Aktif',
        ]);

        $trashedStudent->delete();

        $csvContent = "nis,nama,kelas,status\n2624010,Siswa Baru,Paket C - XI,Aktif\n";
        $file = UploadedFile::fake()->createWithContent('students_reuse_nis.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('students.import'), [
            'file' => $file,
        ]);

        $response
            ->assertRedirect(route('students.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'nis' => '2624010',
            'name' => 'Siswa Baru',
            'deleted_at' => null,
        ]);

        $this->assertStringContainsString(
            '__deleted_'.$trashedStudent->id,
            Student::withTrashed()->find($trashedStudent->id)->nis
        );
    }

    public function test_import_validation_handles_missing_nis_or_invalid_class(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_GURU]);

        $csvContent = "nis,nama,kelas,status\n,No NIS,Paket C - XII,Aktif\n2624009,Daus,Invalid Class Name,Aktif\n";
        $file = UploadedFile::fake()->createWithContent('students_errors.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('students.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('error');

        $error = session('error');
        $this->assertStringContainsString('NIS kosong', $error);
        $this->assertStringContainsString('tidak valid', $error);
    }
}
