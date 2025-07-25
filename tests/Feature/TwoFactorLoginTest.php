<?php

use App\Models\User;

test('user can access 2FA route', function () {
    $response = $this->get('/api/auth/login/2fa-challenge');

    $response->assertStatus(405);
});

test('user that has not enabled 2fa cannot login via 2fa', function() {
    $user = User::factory()->create([
        'email'=> 'example@gmail.com',
        'password' => 'padjU3@',
        'two_factor_secret' => 'randomsecret',
        'two_factor_enabled_at' => now()
    ]);

    $this->postJson('/api/auth/login', [
        'email'=> $user->email,
        'password'=> 'padjU3@'
    ])
    ->assertStatus(202)
    ->assertSeeText('Two-factor authentication required. Please provide your 2FA code.');

    expect($user->hasTwoFactorEnabled())->toBe(true);
});

test('2fa_challenge_key is required and is a uuid for 2fa challenge', function () {
    $this->postJson('api/auth/login/2fa-challenge', [])
        ->assertJsonValidationErrors('2fa_challenge_key')
        ->assertSeeText('The 2fa challenge key field is required.');

    $this->postJson('api/auth/login/2fa-challenge', [
        '2fa_challenge_key' => 'khfoipiep'
    ])->assertSeeText('The 2fa challenge key field must be a valid UUID.');
});

test('either code or recovery_code is needed for 2fa challenge', function() {
    $this->postJson('api/auth/login/2fa-challenge', [])
        ->assertSeeText('Either a 2FA code or a recovery code is required')
        ->assertJsonValidationErrors(['recovery_code','code']);
});

test('recovery_code must be a string', function() {
    $this->postJson('api/auth/login/2fa-challenge', ['recovery_code'=> 124568])
        ->assertSeeText('The recovery code field must be a string')
        ->assertJsonValidationErrors(['recovery_code']);
});

test('only code or recovery will be present at once' , function () {
    $this->postJson('api/auth/login/2fa-challenge', [
        'recovery_code'=> '124568', 'code' => 123456, '2fa_challenge_key' => '6da88dfe-5505-4cbf-bf61-72e131db116e'
    ])->assertJsonValidationErrors(['code','recovery_code'])
        ->assertSeeText('The 2FA code cannot be present when a recovery code is also provided.')
        ->assertSeeText('A recovery code cannot be present when a 2FA code is also provided.');
});

test('code must be numeric and 6 digit', function() {
    $this->postJson('api/auth/login/2fa-challenge', [
        'code' => '1-2456','2fa_challenge_key' => '6da88dfe-5505-4cbf-bf61-72e131db116e'
    ])->assertStatus(422)
        ->assertSeeText('The code field must be a number')
        ->assertJsonValidationErrors(['code']);

    $this->postJson('api/auth/login/2fa-challenge', [
        'code'=> 1234568, '2fa_challenge_key' => '6da88dfe-5505-4cbf-bf61-72e131db116e'
    ])
        ->assertSeeText('The code field must be 6 digits.');
});

test('ensure challenge key is valid' , function () {
    $this->postJson('api/auth/login/2fa-challenge', [
        'code' => 123456, '2fa_challenge_key' => '6da88dfe-5505-4cbf-bf61-72e131db116e'
    ])->assertStatus(422)
        ->assertSeeText('2FA challenge expired or is invalid. Please log in again.');
});

test('2fa must be enabled to login with 2fa', function() {
    $user = User::factory()->create([
        'email'=> 'example@gmail.com',
        'password' => 'padjU3@',
        'two_factor_secret' => 'randomsecret',
        'two_factor_enabled_at' => now()
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email'=> $user->email,
        'password'=> 'padjU3@'
    ]);

    $response->assertStatus(202)
        ->assertSeeText('Two-factor authentication required. Please provide your 2FA code.');

    expect($user->hasTwoFactorEnabled())->toBe(true);
    $fa_code = $response['data']['2fa_challenge_key'];

    $user->forceFill([
        'two_factor_secret'=> null,
        'two_factor_enabled_at' => null
    ]);

    $user->save();

    $this->postJson('api/auth/login/2fa-challenge', [
        'code' => 123456, '2fa_challenge_key' => $fa_code
    ])->assertSeeText('Two-factor authentication not enabled for this account.');
});


test('2fa secret must be valid to login with 2fa', function() {
    $user = User::factory()->create([
        'email'=> 'example@gmail.com',
        'password' => 'padjU3@',
        'two_factor_secret' => 'randomsecret',
        'two_factor_enabled_at' => now()
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email'=> $user->email,
        'password'=> 'padjU3@'
    ]);

    $response->assertStatus(202)
        ->assertSeeText('Two-factor authentication required. Please provide your 2FA code.');

    expect($user->hasTwoFactorEnabled())->toBe(true);
    $fa_code = $response['data']['2fa_challenge_key'];

    $this->postJson('api/auth/login/2fa-challenge', [
        'code' => 123456, '2fa_challenge_key' => $fa_code
    ])->assertSeeText('This secret key is not compatible with Google Authenticator')
    ->assertStatus(500);
});
