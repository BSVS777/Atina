<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Academic\TeacherAssignment\Presentation\Livewire\TeacherAssignmentComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('academic/teacher-assignments', TeacherAssignmentComponent::class)
    ->name('academic.teacher-assignment.index');
