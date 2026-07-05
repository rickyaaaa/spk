<?php

namespace Tests\Feature;

use App\Models\AhpResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_export_ranking_report_as_pdf(): void
    {
        $this->seed();

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reports.export', [
                'period' => 'Genap 2026',
                'format' => 'pdf',
            ]));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_excel_export_uses_final_saw_preference_score(): void
    {
        $this->seed();

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reports.export', [
                'period' => 'Genap 2026',
                'format' => 'excel',
            ]));

        $response->assertOk();

        $csv = ltrim($response->getContent(), "\xEF\xBB\xBF");
        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', $csv)));

        $this->assertSame('sep=,', $lines[0]);

        $header = str_getcsv($lines[1]);
        $nisIndex = array_search('NIS', $header, true);
        $scoreIndex = array_search('Skor Akhir', $header, true);

        $this->assertIsInt($nisIndex);
        $this->assertIsInt($scoreIndex);

        $expectedScores = AhpResult::query()
            ->with('student')
            ->where('evaluation_period', 'Genap 2026')
            ->get()
            ->mapWithKeys(fn (AhpResult $result) => [
                $result->student->nis => number_format((float) $result->final_score, 2, '.', ''),
            ]);

        foreach (array_slice($lines, 2) as $line) {
            $columns = str_getcsv($line);

            $this->assertSame($expectedScores[$columns[$nisIndex]], $columns[$scoreIndex]);
        }
    }
}
