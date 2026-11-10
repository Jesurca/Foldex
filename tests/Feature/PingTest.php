<?php

it('responde el ping de la API v1', function (): void {
    $this->getJson('/api/v1/ping')
        ->assertOk()
        ->assertJson(['status' => 'ok', 'version' => 'v1']);
});
