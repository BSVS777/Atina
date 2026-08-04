<?php

arch('Domain no depende del framework')
    ->expect('Atina\Docencia\Domain')
    ->not->toUse(['Illuminate', 'Livewire', 'Flux']);

arch('Application no depende del framework')
    ->expect('Atina\Docencia\Application')
    ->not->toUse(['Illuminate', 'Livewire', 'Flux']);
