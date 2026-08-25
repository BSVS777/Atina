<?php

use Illuminate\Support\Facades\Route;
use Src\IdentityAccess\Authentication\Presentation\Http\Controllers\AuthController;

Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');

Route::middleware('jwt.auth')->get('/me', [AuthController::class, 'me'])->name('api.me');
