<?php

use Pilot\Core\Support\Updates\PilotUpdateSafety;

it('renders the configured internal update health path', function () {
    config(['cms.updates.health_path' => '/login']);

    expect(app(PilotUpdateSafety::class)->checkApplicationResponse())
        ->toBe('Internal health path /login responded successfully');
});
