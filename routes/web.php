<?php

use Illuminate\Support\Facades\Route;

Route::redirect('dashboard', 'admin/dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
