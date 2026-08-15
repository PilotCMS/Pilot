<?php

test('public registration is disabled after the setup wizard creates the administrator', function () {
    expect(Route::has('register'))->toBeFalse()
        ->and(Route::has('register.store'))->toBeFalse();
});
