<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', \App\Livewire\Admin\Dashboard::class)->name('dashboard');

    // Spaces
    Route::get('/spaces', \App\Livewire\Admin\Spaces\Index::class)->name('spaces.index')->middleware('role:Admin');
    Route::get('/spaces/create', \App\Livewire\Admin\Spaces\Create::class)->name('spaces.create')->middleware('role:Admin');
    Route::get('/spaces/{space}/edit', \App\Livewire\Admin\Spaces\Edit::class)->name('spaces.edit')->middleware('role:Admin');

    // Content
    Route::get('/content', \App\Livewire\Admin\Content\Index::class)->name('content.index');
    Route::get('/content/create', \App\Livewire\Admin\Content\Create::class)->name('content.create');
    Route::get('/content/{content}/edit', \App\Livewire\Admin\Content\Editor::class)->name('content.edit');
    Route::get('/content/{content}/editor', \App\Livewire\Admin\Content\Editor::class)->name('content.editor');
    Route::get('/content/{content}/preview', \App\Http\Controllers\Admin\ContentPreviewController::class)->name('content.preview');
    Route::get('/content-types', \App\Livewire\Admin\ContentTypes\Index::class)->name('content-types.index')->middleware('role:Admin');

    // Block Types
    Route::get('/blocks', \App\Livewire\Admin\Blocks\Index::class)->name('blocks.index')->middleware('role:Admin');
    Route::get('/blocks/create', \App\Livewire\Admin\Blocks\Create::class)->name('blocks.create')->middleware('role:Admin');
    Route::get('/blocks/{blockType}/edit', \App\Livewire\Admin\Blocks\Edit::class)->name('blocks.edit')->middleware('role:Admin');

    // Assets
    Route::get('/assets', \App\Livewire\Admin\Assets\Index::class)->name('assets.index');

    // Datasources
    Route::get('/datasources', \App\Livewire\Admin\Datasources\Index::class)->name('datasources.index');

    // Users
    Route::get('/users', \App\Livewire\Admin\Users\Index::class)->name('users.index')->middleware('role:Admin');

    // Settings
    Route::get('/settings', \App\Livewire\Admin\Settings\Index::class)->name('settings.index')->middleware('role:Admin');
});
