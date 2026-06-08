<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Student;
use App\Services\AhpService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private AhpService $ahpService)
    {
    }

    public function index(): View
    {
        $period = AhpService::DEFAULT_PERIOD;
        $students = $this->ahpService->rankingRows($period);
        $criteria = Criterion::query()->orderBy('id')->get();
        $completion = $this->ahpService->completionPercentage($period);

        return view('dashboard', [
            'students' => $students,
            'criteria' => $criteria,
            'summary' => [
                ['label' => 'Total Siswa', 'value' => Student::query()->count(), 'icon' => 'users'],
                ['label' => 'Periode Aktif', 'value' => $period, 'icon' => 'calendar-check'],
                ['label' => 'Kriteria', 'value' => $criteria->count(), 'icon' => 'sliders-horizontal'],
                ['label' => 'Data Dinilai', 'value' => $completion.'%', 'icon' => 'clipboard-check'],
            ],
        ]);
    }
}
