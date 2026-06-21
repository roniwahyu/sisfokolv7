<?php

use App\Modules\Evaluation\Controllers\GradeEntryController;
use App\Modules\Evaluation\Controllers\RaporController;
use App\Modules\Evaluation\Controllers\CurriculumController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('evaluation')
    ->name('evaluation.')
    ->group(function () {
        // Grade Entry
        Route::get('/grade-entry', [GradeEntryController::class, 'index'])->name('grade-entry.index');
        Route::get('/grade-entry/form', [GradeEntryController::class, 'form'])->name('grade-entry.form');
        Route::post('/grade-entry/save', [GradeEntryController::class, 'storeScores'])->name('grade-entry.store');
        Route::post('/assessments/store', [GradeEntryController::class, 'storeAssessment'])->name('assessments.store');

        // Rapor (RaporController)
        Route::get('/rapor', [RaporController::class, 'index'])->name('rapor.index');
        Route::get('/rapor/{student}', [RaporController::class, 'show'])->name('rapor.show');
        Route::get('/rapor/{student}/pdf', [RaporController::class, 'downloadPdf'])->name('rapor.pdf');

        // Curriculum / CP & Materi Ajar (CurriculumController)
        Route::get('/curriculum', [CurriculumController::class, 'index'])->name('curriculum.index');
        Route::get('/curriculum/create', [CurriculumController::class, 'create'])->name('curriculum.create');
        Route::post('/curriculum', [CurriculumController::class, 'store'])->name('curriculum.store');
    });
