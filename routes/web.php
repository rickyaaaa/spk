<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/template', [StudentController::class, 'exportTemplate'])->name('students.template');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    Route::put('/criteria/comparisons', [CriterionController::class, 'updateComparisons'])->name('criteria.comparisons.update');
    Route::resource('criteria', CriterionController::class)->except(['create', 'edit']);
    Route::resource('users', UserController::class)->except(['create']);

    Route::get('/scores', [ScoreController::class, 'index'])->name('scores.index');
    Route::get('/scores/template', [ScoreController::class, 'exportTemplate'])->name('scores.template');
    Route::post('/scores/import', [ScoreController::class, 'import'])->name('scores.import');
    Route::put('/scores', [ScoreController::class, 'update'])->name('scores.update');

    Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
    Route::post('/ranking/calculate', [RankingController::class, 'calculate'])->name('ranking.calculate');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});

Route::get('/clear-route-cache', function () {
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    return 'Route cache berhasil dihapus!';
});

Route::get('/run-migrations', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    return 'Database migrations ran successfully!';
});


