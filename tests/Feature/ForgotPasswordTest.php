<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;

$url = '/api/auth/forgot-password';

test('forgot passowrd request api exists', function () use ($url){
    $response = $this->get($url);

    $response->assertStatus(405);
});

test('user can trigger forgot password with email and callback url required', function() use ($url) {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url,[]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'callbackUrl'])
        ->assertSeeText('The email field is required.');
    $response->assertSeeText('The callback url field is required.');
});

test('email is valid', function() use ($url) {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url, [
        'email' => 'invalid-email',
        'callbackUrl' => 'https://example.com/callback'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email'])
        ->assertSeeText('The email field must be a valid email address.');
});

test('email exists in users table', function() use ($url) {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url, [
        'email' => 'example@test.com',
        'callbackUrl' => 'https://example.com/callback'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email'])
        ->assertSeeText('The selected email is invalid.');
});

test('callbackUrl is valid', function() use ($url) {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url, [
        'email' => 'example@test.com',
        'callbackUrl' => 'invalid-url'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['callbackUrl'])
        ->assertSeeText('The callback url field must be a valid URL.');
});

test('user can trigger forgot password with valid email and callbackUrl', function() use ($url) {

    Config::set('mail.mailer', 'log');
    Config::set('queue.default', 'sync');

    $user = User::factory()->create([
        'email' => 'example@test.com',
    ]);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url, [
        'email' => $user->email,
        'callbackUrl' => 'https://example.com/callback'
    ]);

    $response->assertStatus(200);
    $response->assertSeeText('We have emailed your password reset link.');

});
