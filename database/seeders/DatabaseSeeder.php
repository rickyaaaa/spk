<?php

namespace Database\Seeders;

use App\Models\Criterion;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\User;
use App\Services\AhpService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@skb26.sch.id'],
            [
                'name' => 'Admin SKB 26',
                'password' => Hash::make('password'),
            ]
        );

        $criteria = [
            ['code' => 'rapor', 'name' => 'Nilai Rapor', 'description' => 'Prioritas utama berdasarkan nilai akademik semester berjalan.'],
            ['code' => 'tugas', 'name' => 'Tugas', 'description' => 'Konsistensi penyelesaian tugas dan kualitas kerja harian.'],
            ['code' => 'kehadiran', 'name' => 'Kehadiran', 'description' => 'Disiplin hadir dalam kegiatan belajar SKB 26.'],
        ];

        foreach ($criteria as $criterion) {
            Criterion::query()->updateOrCreate(
                ['code' => $criterion['code']],
                $criterion
            );
        }

        $students = [
            ['nis' => '2624001', 'name' => 'Alya Rahmadani', 'class_name' => 'Paket C - XII', 'scores' => ['rapor' => 92, 'tugas' => 88, 'kehadiran' => 96]],
            ['nis' => '2624002', 'name' => 'Bima Pratama', 'class_name' => 'Paket C - XII', 'scores' => ['rapor' => 88, 'tugas' => 91, 'kehadiran' => 92]],
            ['nis' => '2624003', 'name' => 'Citra Lestari', 'class_name' => 'Paket B - IX', 'scores' => ['rapor' => 86, 'tugas' => 87, 'kehadiran' => 98]],
            ['nis' => '2624004', 'name' => 'Daffa Maulana', 'class_name' => 'Paket C - XI', 'scores' => ['rapor' => 84, 'tugas' => 82, 'kehadiran' => 90]],
            ['nis' => '2624005', 'name' => 'Eka Safitri', 'class_name' => 'Paket B - VIII', 'scores' => ['rapor' => 80, 'tugas' => 85, 'kehadiran' => 88]],
        ];

        $ahpService = app(AhpService::class);

        foreach ($students as $studentData) {
            $student = Student::query()->updateOrCreate(
                ['nis' => $studentData['nis']],
                [
                    'name' => $studentData['name'],
                    'class_name' => $studentData['class_name'],
                    'status' => 'Aktif',
                ]
            );

            foreach ($studentData['scores'] as $criterionCode => $score) {
                $criterion = Criterion::query()->where('code', $criterionCode)->firstOrFail();

                StudentScore::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'criterion_id' => $criterion->id,
                        'evaluation_period' => AhpService::DEFAULT_PERIOD,
                    ],
                    [
                        'raw_score' => $score,
                        'score' => $ahpService->standardizeAlternativeScore($score),
                    ]
                );
            }
        }

        $criteriaByCode = Criterion::query()->get()->keyBy('code');

        $ahpService->updateWeights([
            $criteriaByCode['rapor']->id.'_'.$criteriaByCode['tugas']->id => 3,
            $criteriaByCode['rapor']->id.'_'.$criteriaByCode['kehadiran']->id => 5,
            $criteriaByCode['tugas']->id.'_'.$criteriaByCode['kehadiran']->id => 3,
        ]);

        $ahpService->calculateRanking(AhpService::DEFAULT_PERIOD);
    }
}
