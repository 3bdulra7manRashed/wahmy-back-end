<?php

declare(strict_types=1);

it('returns health endpoint successfully', function () {
    $response = $this->getJson('/api/health');

    $response->assertStatus(200)
        ->assertJson(['status' => 'ok']);
});
