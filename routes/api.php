<?php

use Illuminate\Support\Facades\Route;
use Src\Academic\AcademicCredential\Presentation\Http\Controllers\InstitutionSearchController;
use Src\IdentityAccess\Authentication\Presentation\Http\Controllers\AuthController;

Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');

Route::middleware('jwt.auth')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    Route::get('/institutions/search', InstitutionSearchController::class)->name('api.institutions.search');
});
