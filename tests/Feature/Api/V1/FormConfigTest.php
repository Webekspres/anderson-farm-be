<?php

use App\Models\FormConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    Sanctum::actingAs($this->user, ['*']);
});

it('can list form configs', function () {
    $ENDPOINT = '/api/v1/form-configs';
    FormConfig::factory()->count(3)->create();
    $response = $this->getJson($ENDPOINT);
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});

it('can create form config', function () {
    $ENDPOINT = '/api/v1/form-configs';
    $payload = FormConfig::factory()->make()->toArray();
    // config_value harus string JSON
    if (is_array($payload['config_value'])) {
        $payload['config_value'] = json_encode($payload['config_value']);
    }
    $response = $this->postJson($ENDPOINT, $payload);
    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});

it('can show form config', function () {
    $ENDPOINT = '/api/v1/form-configs';
    $formConfig = FormConfig::factory()->create();
    $response = $this->getJson($ENDPOINT . '/' . $formConfig->id);
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});

it('can update form config', function () {
    $ENDPOINT = '/api/v1/form-configs';
    $formConfig = FormConfig::factory()->create();
    $payload = ['key_name' => 'updated_key_name'];
    $response = $this->putJson($ENDPOINT . '/' . $formConfig->id, $payload);
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});

it('can delete form config', function () {
    $ENDPOINT = '/api/v1/form-configs';
    $formConfig = FormConfig::factory()->create();
    $response = $this->deleteJson($ENDPOINT . '/' . $formConfig->id);
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});
