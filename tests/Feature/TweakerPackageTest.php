<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tweaker endpoints are gated when disabled', function () {
    config(['tweaker.enabled' => false]);

    $this->get(route('tweaker.script'))->assertStatus(403);
    $this->postJson(route('tweaker.save'), [])->assertStatus(403);
});

test('tweaker can save less output when enabled', function () {
    $path = base_path('storage/app/tweaker/test.less');

    config([
        'tweaker.enabled' => true,
        'tweaker.allowed_paths' => [base_path('storage/app/tweaker')],
        'tweaker.default_less_path' => 'storage/app/tweaker/test.less',
    ]);

    if (file_exists($path)) {
        unlink($path);
    }

    $response = $this->postJson(route('tweaker.save'), [
        'kind' => 'less',
        'rules' => [
            [
                'selector' => '.card',
                'declarations' => [
                    'color' => '#fff',
                ],
            ],
        ],
    ]);

    $response->assertOk();
    expect($path)->toBeFile();
    expect(file_get_contents($path))->toContain('.card');
});

test('tweaker script endpoint serves javascript when enabled', function () {
    config(['tweaker.enabled' => true]);

    $response = $this->get(route('tweaker.script'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/javascript; charset=UTF-8');
});

test('tweaker can update model field when enabled', function () {
    config([
        'tweaker.enabled' => true,
        'tweaker.allowed_models' => [User::class],
        'tweaker.allowed_fields' => ['name'],
    ]);

    $user = User::factory()->create(['name' => 'Original']);

    $response = $this->postJson(route('tweaker.model.update'), [
        'model' => User::class,
        'id' => $user->id,
        'field' => 'name',
        'value' => 'Updated',
    ]);

    $response->assertOk();
    expect($user->fresh()->name)->toBe('Updated');
});
