<?php

it('publishes public installation documentation for Pilot, Incontext, and the Laravel connector', function () {
    $documentationPath = public_path('docs/index.html');

    expect($documentationPath)->toBeFile();

    $documentation = file_get_contents($documentationPath);

    expect($documentation)
        ->toContain('<title>Pilot CMS Documentation</title>')
        ->toContain('id="cms-installation"')
        ->toContain('Install Pilot CMS')
        ->toContain('id="connector-installation"')
        ->toContain('Install Laravel Connector')
        ->toContain('composer require pilot/laravel')
        ->toContain('id="incontext-installation"')
        ->toContain('Install Incontext')
        ->toContain('PILOT_IN_CONTEXT_ENABLED=true')
        ->toContain('GET   /_pilot/preview/{content}')
        ->toContain('Preview iframe headers')
        ->toContain('X-Frame-Options: SAMEORIGIN')
        ->toContain('frame-ancestors')
        ->toContain('PATCH /_pilot/in-context/blocks/{block}');
});
