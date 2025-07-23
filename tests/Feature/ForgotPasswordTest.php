<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;

test('forgot passowrd request api exists', function () {
    $response = $this->get('/api/auth/forgot-password');

    $response->assertStatus(405);
});


test('user can trigger forgot password with email and callback url required', function() {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson('/api/auth/forgot-password',[]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'callbackUrl'])
        ->assertSeeText('The email field is required.');
    $response->assertSeeText('The callback url field is required.');
});

test('email is valid', function() {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson('/api/auth/forgot-password', [
        'email' => 'invalid-email',
        'callbackUrl' => 'https://example.com/callback'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email'])
        ->assertSeeText('The email field must be a valid email address.');
});

test('email exists in users table', function() {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson('/api/auth/forgot-password', [
        'email' => 'example@test.com',
        'callbackUrl' => 'https://example.com/callback'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email'])
        ->assertSeeText('The selected email is invalid.');
});

test('callbackUrl is valid', function() {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson('/api/auth/forgot-password', [
        'email' => 'example@test.com',
        'callbackUrl' => 'invalid-url'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['callbackUrl'])
        ->assertSeeText('The callback url field must be a valid URL.');
});

test('user can trigger forgot password with valid email and callbackUrl', function() {

    Config::set('mail.mailer', 'log');
    Config::set('queue.default', 'sync');

    $user = User::factory()->create([
        'email' => 'example@test.com',
    ]);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson('/api/auth/forgot-password', [
        'email' => $user->email,
        'callbackUrl' => 'https://example.com/callback'
    ]);

    $response->assertStatus(200);
    $response->assertSeeText('We have emailed your password reset link.');

});
