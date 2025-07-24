<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

test('login route exists and it is a post', function () {
    $response = $this->get('/api/auth/login');

    expect($response->status())->toBe(405);
    expect($response->status())->not()->toBe(404);
});

test('email is required for login', function () {
    $response = $this->postJson('/api/auth/login', [
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertSeeText('The email field is required.');
});

test('password is required for login', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'example@test.com'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText('The password field is required.');
});

test('email must exist in users table', function () {

    $user = User::factory()->create([
        'email' => 'jumpstart@test.com'
    ]);

    $response = $this->postJson('api/auth/login',[
        'email' => $user->email,
        'password' => 'wrongpassword'
    ]);

    expect($response->status())->not()->toBe(422);

});

test('email must be a valid email address', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'invalid-email',
        'password' => 'password123'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertSeeText('The email field must be a valid email address.');
});

test('password must be a string', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'example@test.com',
        'password' => 123456
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText('The password field must be a string.');
});

test('user cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'jumpstart@test.com',
        'password' => Hash::make('password123')
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password'
    ]);

    $response->assertStatus(404);
    $response->assertSeeText('Invalid Login Credential');

});

beforeEach(function () {
    Artisan::call('passport:client', [
        '--personal' => true,
        '--name' => 'Test Personal Access Client',
        '--provider' => 'users',
    ]);
});

test('user can login with valid credentials', function () {

    $user = User::factory()->create([
        'email' => 'jumpstart@test.com',
        'password' => Hash::make('password123')
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123'
    ]);

    logger()->info('Login response', [
        'response' => $response->getContent(),
        'status' => $response->status(),
    ]);
    $response->assertStatus(200);
    $response->assertSeeText('Login successful');
    $response->assertSeeText('token');
    expect($response->json('data.user.id'))->toBe($user->id);

});

