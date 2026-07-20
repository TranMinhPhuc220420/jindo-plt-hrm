<?php

test('api health returns success envelope', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'ok')
        ->assertJsonStructure([
            'success',
            'data' => [
                'status',
                'app',
                'environment',
            ],
        ]);
});

test('unknown api route returns not found envelope', function () {
    $response = $this->getJson('/api/does-not-exist');

    $response->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error_code', 'NOT_FOUND');
});
