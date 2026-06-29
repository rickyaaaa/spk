<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\User;
use App\Services\AhpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ScoreImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_download_score_template(): void
    {
        $admin = User::factory()->create();
        
        // Seed some students and criteria
        $student = Student::query()->create(['nis' => '12345', 'name' => 'Budi', 'class_name' => 'Paket C - XII']);
        $criterion = Criterion::query()->create(['code' => 'rapor', 'name' => 'Rapor', 'weight' => 0.5]);

        $response = $this->actingAs($admin)->get(route('scores.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        
        // Verify header
        $this->assertEquals('nis,nama,rapor', trim($lines[0]));
        // Verify data
        $this->assertEquals('12345,Budi,', trim($lines[1]));
    }

    public function test_can_import_scores_successfully(): void
    {
        $admin = User::factory()->create();
        $student = Student::query()->create(['nis' => '12345', 'name' => 'Budi', 'class_name' => 'Paket C - XII']);
        $criterion = Criterion::query()->create(['code' => 'rapor', 'name' => 'Rapor', 'weight' => 1.0]);

        // Pre-populate comparison matrix so calculation works (is consistent)
        \App\Models\AhpComparison::query()->create([
            'criterion_a_id' => $criterion->id,
            'criterion_b_id' => $criterion->id,
            'value' => 1.0,
        ]);

        $csvContent = "nis,nama,rapor\n12345,Budi,85.5\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('scores.import'), [
            'file' => $file,
            'period' => AhpService::DEFAULT_PERIOD,
        ]);

        $response->assertRedirect(route('scores.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('student_scores', [
            'student_id' => $student->id,
            'criterion_id' => $criterion->id,
            'raw_score' => 85.5,
            'evaluation_period' => AhpService::DEFAULT_PERIOD,
        ]);
    }

    public function test_import_validation_handles_missing_nis_or_invalid_scores(): void
    {
        $admin = User::factory()->create();
        $student = Student::query()->create(['nis' => '12345', 'name' => 'Budi', 'class_name' => 'Paket C - XII']);
        $criterion = Criterion::query()->create(['code' => 'rapor', 'name' => 'Rapor', 'weight' => 1.0]);

        $csvContent = "nis,nama,rapor\n12345,Budi,150\n99999,Salah,80\n";
        $file = UploadedFile::fake()->createWithContent('scores_errors.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('scores.import'), [
            'file' => $file,
            'period' => AhpService::DEFAULT_PERIOD,
        ]);

        $response->assertRedirect(route('scores.index'));
        $response->assertSessionHas('error');
        
        $error = session('error');
        $this->assertStringContainsString('Nilai \'150\' tidak valid', $error);
        $this->assertStringContainsString('Siswa dengan NIS \'99999\' tidak ditemukan', $error);
    }
}
