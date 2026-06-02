<?php

use Illuminate\Support\Facades\Route;
use Tweaker\Http\Controllers\TweakerController;

Route::middleware(config('tweaker.route_middleware', ['web']))
    ->prefix(config('tweaker.route_prefix', '_tweaker'))
    ->group(function () {
        Route::get('/script', [TweakerController::class, 'script'])->name('tweaker.script');
        Route::get('/config', [TweakerController::class, 'config'])->name('tweaker.config');
        Route::post('/save', [TweakerController::class, 'save'])->name('tweaker.save');
        Route::post('/model', [TweakerController::class, 'updateModel'])->name('tweaker.model.update');
    });
