<?php

use Illuminate\Support\Facades\Artisan;

test('route to send email verification exists', function () {
    $response = $this->get('api/auth/email/resend-verification');

    $response->assertStatus(405);
});

test('must be logged in to request email verification', function () {
    $this->postJson('api/auth/email/resend-verification')
        ->assertStatus(401)
        ->assertSeeText('Unauthenticated.');

});

beforeEach(function () {
    Artisan::call('passport:client', [
        '--personal' => true,
        '--name' => 'Test Personal Access Client',
        '--provider' => 'users',
    ]);
});

test('ensure callback url is present and valid', function () {
    $data = createUserAndGenerateToken();
    $token = $data['token'];

    $this
        ->withHeaders([
        'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])
        ->postJson('/api/auth/email/resend-verification',[])
        ->assertStatus(422)
        ->assertSeeText('The callback url field is required');

    $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])
        ->postJson('/api/auth/email/resend-verification',[
            'callbackUrl' => 'invalid-url'
        ])
        ->assertStatus(422)
        ->assertSeeText('The callback url field must be a valid URL');

});

test('only unverified user can request verification email', function () {
    $data = createUserAndGenerateToken();
    $token = $data['token'];
    $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])
        ->postJson('/api/auth/email/resend-verification',[
            'callbackUrl' => 'https://example.test.com'
        ])
        ->assertStatus(400)
        ->assertSeeText('User already has a verified email');

});


test('unverified user can request verification email', function () {
    $data = createUnverifiedUserAndGenerateToken();
    $token = $data['token'];
    $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])
        ->postJson('/api/auth/email/resend-verification',[
            'callbackUrl' => 'https://example.test.com'
        ])
        ->assertStatus(200)
        ->assertSeeText('We have sent you another email verification link');

});

test('ensure verify email route exists and requires authentication' , function() {
    $this->getJson('api/auth/email/verify')
        ->assertStatus(401)
        ->assertSeeText('Unauthenticated.');

});

test('ensure email verification link is valid', function () {
    $data = createUnverifiedUserAndGenerateToken();
    $token = $data['token'];
    $url = 'api/auth/email/verify?expires=1753440231&hash=d9485ba98d22eaf58bc6247b142f948d5947772b&id=4&signature=692e8a71fe3660a9834722eec981fc45d8fe8b5172091c23df8da681a04ae0e7';

    $this->withHeaders([
        'Authorization' => 'Bearer '. $token
    ])
    ->getJson($url)
    ->assertSeeText('Invalid signature.');

});

test('verify user email', function () {
    $data = createUnverifiedUserAndGenerateToken();
    $token = $data['token'];
    $user = $data['user'];

    $param = verificationUrlParam($user);
    $url = 'api/auth/email/verify?'. $param;

    $this->withHeaders([
        'Authorization' => 'Bearer '. $token
    ])
    ->getJson($url)
    ->assertSeeText('User email verified successfully!');

    $user->refresh();
    expect($user->hasVerifiedEmail())->toBe(true);
    expect($user->email_verified_at)->not()->toBe(null);

});



