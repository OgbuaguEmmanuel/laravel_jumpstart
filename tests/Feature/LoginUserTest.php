<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

$url = '/api/V1/auth/login';

test('login route exists and it is a post', function () use ($url) {
    $response = $this->get($url);

    expect($response->status())->toBe(405);
    expect($response->status())->not()->toBe(404);
});

test('email is required for login', function () use ($url) {
    $response = $this->postJson($url, [
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertSeeText('The email field is required.');
});

test('password is required for login', function () use ($url) {
    $response = $this->postJson($url, [
        'email' => 'example@test.com'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText('The password field is required.');
});

test('email must exist in users table', function () use ($url){

    $user = User::factory()->create([
        'email' => 'jumpstart@test.com'
    ]);

    $response = $this->postJson($url,[
        'email' => $user->email,
        'password' => 'wrongpassword',
        'callbackUrl' => 'https://example.test.com'

    ]);

    expect($response->status())->not()->toBe(422);

});

test('email must be a valid email address', function () use ($url) {
    $response = $this->postJson($url, [
        'email' => 'invalid-email',
        'password' => 'password123'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertSeeText('The email field must be a valid email address.');
});

test('password must be a string', function () use ($url) {
    $response = $this->postJson($url, [
        'email' => 'example@test.com',
        'password' => 123456
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText('The password field must be a string.');
});

test('user cannot login with invalid credentials', function () use ($url) {
    $user = User::factory()->create([
        'email' => 'jumpstart@test.com',
        'password' => Hash::make('password123')

    ]);

    $response = $this->postJson($url, [
        'email' => $user->email,
        'password' => 'password',
        'callbackUrl' => 'https://example.test.com'

    ]);

    $response->assertStatus(404);
    $response->assertSeeText('Invalid Login Credential');

});

test('user can login with valid credentials', function () use ($url) {

    $user = User::factory()->create([
        'email' => 'jumpstart@test.com',
        'password' => Hash::make('password123')
    ]);

    $response = $this->postJson($url, [
        'email' => $user->email,
        'password' => 'password123',
        'callbackUrl' => 'https://example.test.com'

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

