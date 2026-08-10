<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Academic\Teacher\Presentation\Livewire\TeacherComponent;
use Src\Academic\Teacher\Presentation\Livewire\TeacherProfileComponent;

Route::middleware(['web', 'auth', 'verified'])->prefix('academic')->name('academic.')->group(function () {
    Route::get('teachers', TeacherComponent::class)->name('teacher.index');
    Route::get('teachers/{teacher}', TeacherProfileComponent::class)->name('teacher.profile');
});
