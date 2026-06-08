<?php

test('button icons are aligned inline with labels', function () {
    $stylesheet = file_get_contents(resource_path('css/app.css'));

    expect($stylesheet)
        ->toContain('[data-flux-button]')
        ->toContain('display: inline-flex')
        ->toContain('align-items: center')
        ->toContain('[data-flux-button] > svg')
        ->toContain('.admin-app button > i[class^="ph"]');
});
