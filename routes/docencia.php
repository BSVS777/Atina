<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('docencia')->name('docencia.')->group(function () {
    Route::livewire('docentes', 'pages::docencia.docentes')->name('docentes.index');
    Route::livewire('docentes/{docente}', 'pages::docencia.docente-perfil')->name('docentes.perfil');
});
