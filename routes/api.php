<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/spaces/{space}/contents', [\App\Http\Controllers\Api\ContentController::class, 'index']);
    Route::get('/spaces/{space}/contents/{slug}', [\App\Http\Controllers\Api\ContentController::class, 'show']);

    // Preview: signed URL returns draft content (no auth required, signature validates)
    Route::get('/preview/{content}', [\App\Http\Controllers\Api\PreviewController::class, 'show'])
        ->middleware('signed')
        ->name('api.preview.show');
    Route::post('/preview/render', \App\Http\Controllers\Api\LivePreviewController::class)
        ->name('api.preview.render');

    // Draft access is guarded inside ContentController so published delivery stays public.
});
