<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

beforeEach(function () {
    Storage::fake('public');
});

describe('Upload API', function () {
    // Helper: Auth user
    function authUser()
    {
        return User::factory()->create();
    }

    // POST: Happy Path
    it('successfully uploads an image', function () {
        $user = authUser();
        $file = UploadedFile::fake()->image('test.jpg');
        $response = $this->actingAs($user)->postJson('/api/v1/uploads', [
            'file' => $file,
            'folder' => 'photos',
        ]);
        $response->assertCreated();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['image_url', 'image_path_local']
        ]);
        $path = $response->json('data.image_path_local');
        Storage::disk('public')->assertExists($path);
    });

    // POST: Edge Case - folder tidak valid
    it('returns 422 if folder is not allowed', function () {
        $user = authUser();
        $file = UploadedFile::fake()->image('test.jpg');
        $response = $this->actingAs($user)->postJson('/api/v1/uploads', [
            'file' => $file,
            'folder' => 'notallowed',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('folder');
    });

    // POST: Edge Case - file terlalu besar
    it('returns 422 if file is too large', function () {
        $user = authUser();
        $file = UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf');
        $response = $this->actingAs($user)->postJson('/api/v1/uploads', [
            'file' => $file,
            'folder' => 'documents',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    });

    // POST: Edge Case - ekstensi tidak diizinkan
    it('returns 422 if file extension is not allowed', function () {
        $user = authUser();
        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream');
        $response = $this->actingAs($user)->postJson('/api/v1/uploads', [
            'file' => $file,
            'folder' => 'contracts',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    });

    // DELETE: Happy Path
    it('successfully deletes a file', function () {
        $user = authUser();
        $file = UploadedFile::fake()->image('delete.jpg');
        $path = $file->storeAs('photos', 'delete.jpg', 'public');
        Storage::disk('public')->assertExists($path);
        $response = $this->actingAs($user)->deleteJson('/api/v1/uploads', [
            'file_path' => $path,
        ]);
        $response->assertOk();
        $response->assertJson(['success' => true]);
        Storage::disk('public')->assertMissing($path);
    });

    // DELETE: Edge Case - file tidak ditemukan
    it('returns 404 if file not found', function () {
        $user = authUser();
        $response = $this->actingAs($user)->deleteJson('/api/v1/uploads', [
            'file_path' => 'photos/notfound.jpg',
        ]);
        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    });

    // AUTH: Tanpa token
    it('returns 401 if not authenticated (POST)', function () {
        $file = UploadedFile::fake()->image('test.jpg');
        $response = $this->postJson('/api/v1/uploads', [
            'file' => $file,
            'folder' => 'photos',
        ]);
        $response->assertUnauthorized();
    });

    it('returns 401 if not authenticated (DELETE)', function () {
        $response = $this->deleteJson('/api/v1/uploads', [
            'file_path' => 'photos/abc.jpg',
        ]);
        $response->assertUnauthorized();
    });
});
