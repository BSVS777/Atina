<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Academic\AffinityCatalog\Presentation\Livewire\AffinityCatalogComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('academic/affinity-catalog', AffinityCatalogComponent::class)
    ->name('academic.affinity-catalog.index');
