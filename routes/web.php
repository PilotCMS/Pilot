<?php

use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/setup.php';

Route::get('/', [PageController::class, 'home'])->name('home');

Route::redirect('dashboard', 'admin/dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';

Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '.*')
    ->name('site.page');
