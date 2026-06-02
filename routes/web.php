<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Site\PageController::class, 'home'])->name('home');

Route::redirect('dashboard', 'admin/dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';

Route::get('/{slug}', [\App\Http\Controllers\Site\PageController::class, 'show'])
    ->where('slug', '.*')
    ->name('site.page');
