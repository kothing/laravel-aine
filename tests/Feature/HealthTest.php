<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_returns_json_with_status(): void
    {
        $this->getJson('/health')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    public function test_up_returns_200_empty(): void
    {
        $this->get('/up')->assertStatus(200);
    }
}