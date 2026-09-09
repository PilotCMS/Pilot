<?php

use Illuminate\Support\Facades\Route;

it('does not register client-facing or html-rendering routes', function () {
    expect(Route::has('home'))->toBeFalse()
        ->and(Route::has('site.page'))->toBeFalse()
        ->and(Route::has('admin.content.preview'))->toBeFalse()
        ->and(Route::has('api.preview.render'))->toBeFalse()
        ->and(Route::has('pilot.preview'))->toBeFalse();

    $this->get('/')->assertNotFound();
});
