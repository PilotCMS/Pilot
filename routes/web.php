<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Site\PageController::class, 'home'])->name('home');

Route::redirect('dashboard', 'admin/dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';

Route::middleware(['auth'])->prefix('_cms_editor')->name('cms.frontend-editor.')->group(function () {
    Route::get('/script', [\App\Http\Controllers\Cms\FrontendEditorController::class, 'script'])->name('script');
    Route::get('/blocks/{block}', [\App\Http\Controllers\Cms\FrontendEditorController::class, 'show'])->name('blocks.show');
    Route::patch('/blocks/{block}', [\App\Http\Controllers\Cms\FrontendEditorController::class, 'update'])->name('blocks.update');
});

Route::get('/{slug}', [\App\Http\Controllers\Site\PageController::class, 'show'])
    ->where('slug', '.*')
    ->name('site.page');
