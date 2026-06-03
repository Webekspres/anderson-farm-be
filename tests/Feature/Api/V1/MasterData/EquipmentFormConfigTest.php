<?php

declare(strict_types=1);

use App\Models\EquipmentType;
use App\Models\EquipmentTypeFormConfig;
use App\Models\FormConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

const EQUIPMENT_FORM_CONFIG_ENDPOINT = '/api/v1/equipment-types/%s/form-configs';

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($this->admin);
    $this->equipmentType = EquipmentType::factory()->create();
});

it('returns form configs with decoded ui_metadata', function () {
    $formConfig = FormConfig::factory()->create([
        'category' => 'EQUIPMENT',
        'key_name' => 'temp_sensor_1',
        'config_value' => json_encode([
            'type' => 'number',
            'min' => 0,
            'max' => 50,
            'label' => 'Suhu Kandang',
        ], JSON_THROW_ON_ERROR),
    ]);

    EquipmentTypeFormConfig::factory()->create([
        'equipment_type_id' => $this->equipmentType->id,
        'form_config_id' => $formConfig->id,
        'display_order' => 1,
    ]);

    $response = $this->getJson(sprintf(EQUIPMENT_FORM_CONFIG_ENDPOINT, $this->equipmentType->id));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $formConfig->id)
        ->assertJsonPath('data.0.category', 'EQUIPMENT')
        ->assertJsonPath('data.0.key_name', 'temp_sensor_1')
        ->assertJsonPath('data.0.ui_metadata.type', 'number')
        ->assertJsonPath('data.0.ui_metadata.label', 'Suhu Kandang');

    expect($response->json('data.0.ui_metadata'))->toBeArray()
        ->and($response->json('data.0.ui_metadata'))->not->toBeString();
});

it('syncs form config mapping and removes stale pivot rows', function () {
    $oldForm = FormConfig::factory()->create();
    $newFormA = FormConfig::factory()->create();
    $newFormB = FormConfig::factory()->create();

    EquipmentTypeFormConfig::factory()->create([
        'equipment_type_id' => $this->equipmentType->id,
        'form_config_id' => $oldForm->id,
        'display_order' => 1,
    ]);

    $response = $this->postJson(sprintf(EQUIPMENT_FORM_CONFIG_ENDPOINT, $this->equipmentType->id), [
        'form_config_ids' => [$newFormA->id, $newFormB->id],
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');

    expect($this->equipmentType->formConfigs()->pluck('form_configs.id')->all())
        ->toBe([$newFormA->id, $newFormB->id]);

    $this->assertDatabaseMissing('equipment_type_form_configs', [
        'equipment_type_id' => $this->equipmentType->id,
        'form_config_id' => $oldForm->id,
        'deleted_at' => null,
    ]);
});

it('returns 422 when form_config_ids contains invalid uuid', function () {
    $response = $this->postJson(sprintf(EQUIPMENT_FORM_CONFIG_ENDPOINT, $this->equipmentType->id), [
        'form_config_ids' => [(string) Str::uuid()],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['form_config_ids.0']);
});

it('returns 404 when equipment type does not exist', function () {
    $response = $this->getJson(sprintf(EQUIPMENT_FORM_CONFIG_ENDPOINT, (string) Str::uuid()));

    $response->assertNotFound();
});
