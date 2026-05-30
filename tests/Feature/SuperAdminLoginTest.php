<?php

use App\Models\User;
use Database\Seeders\SuperAdminRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withToken;

uses(RefreshDatabase::class);

test('super admin can login and get auth data', function () {
    seed(SuperAdminRoleSeeder::class);

    postJson('/api/auth/login', [
        'identifier' => 'super-admin@gmail.com',
        'password' => 'password',
        'device_name' => 'test-device',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', 'super-admin@gmail.com')
        ->assertJsonPath('data.user.roles.0.name', 'super admin')
        ->assertJsonPath('data.user.permissions', []);
});

test('user without super admin role cannot login', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    postJson('/api/auth/login', [
        'identifier' => 'user@example.com',
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('identifier');
});

test('super admin can fetch authenticated user data', function () {
    seed(SuperAdminRoleSeeder::class);

    $user = User::query()
        ->where('email', 'super-admin@gmail.com')
        ->firstOrFail();

    $token = $user->createToken('test-device')->plainTextToken;

    withToken($token);

    getJson('/api/auth/user')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', 'super-admin@gmail.com')
        ->assertJsonPath('data.roles.0.name', 'super admin')
        ->assertJsonPath('data.permissions', []);
});

test('authenticated user data requires sanctum token', function () {
    getJson('/api/auth/user')
        ->assertUnauthorized();
});
