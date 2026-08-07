<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['filesystems.uploads' => 'public']);
    Storage::fake('public');
});

describe('Upload API', function () {
    function authUser()
    {
        return User::factory()->create();
    }

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
            'data' => ['image_url', 'image_path_local'],
        ]);
        $path = $response->json('data.image_path_local');
        expect($path)->toStartWith('photos/'.now()->format('Y/m').'/');
        Storage::disk('public')->assertExists($path);
    });

    it('stores contracts under contracts/YYYY/MM prefix', function () {
        $user = authUser();
        $file = UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf');
        $response = $this->actingAs($user)->postJson('/api/v1/uploads', [
            'file' => $file,
            'folder' => 'contracts',
        ]);
        $response->assertCreated();
        $path = $response->json('data.image_path_local');
        expect($path)->toStartWith('contracts/'.now()->format('Y/m').'/');
        Storage::disk('public')->assertExists($path);
    });

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

    it('successfully deletes a file', function () {
        $user = authUser();
        $file = UploadedFile::fake()->image('delete.jpg');
        $path = $file->storeAs('photos/'.now()->format('Y/m'), 'delete.jpg', 'public');
        Storage::disk('public')->assertExists($path);
        $response = $this->actingAs($user)->deleteJson('/api/v1/uploads', [
            'file_path' => $path,
        ]);
        $response->assertOk();
        $response->assertJson(['success' => true]);
        Storage::disk('public')->assertMissing($path);
    });

    it('returns 404 if file not found', function () {
        $user = authUser();
        $response = $this->actingAs($user)->deleteJson('/api/v1/uploads', [
            'file_path' => 'photos/notfound.jpg',
        ]);
        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    });

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
