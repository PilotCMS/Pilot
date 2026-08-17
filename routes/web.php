<?php

use Illuminate\Support\Facades\Route;
use Pilot\Core\Http\Controllers\Site\PageController;

Route::get('/', [PageController::class, 'home'])->name('home');

Route::redirect('dashboard', 'admin/dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '.*')
    ->name('site.page');
