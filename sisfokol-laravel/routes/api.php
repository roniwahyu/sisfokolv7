<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\ScheduleController as ApiScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [ApiAuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [ApiAuthController::class, 'user'])->name('api.user');
    Route::post('/logout', [ApiAuthController::class, 'logout'])->name('api.logout');

    Route::get('/schedules/today', [ApiScheduleController::class, 'today'])->name('api.schedules.today');
});
