<?php

$url = 'api/V1/auth/email/resend-verification';
test('route to send email verification exists', function () use ($url) {
    $response = $this->get($url);

    $response->assertStatus(405);
});

test('must be logged in to request email verification', function () use ($url) {
    $this->postJson($url)
        ->assertStatus(401)
        ->assertSeeText('Log in to perform this action.');

});

test('ensure callback url is present and valid', function () use ($url) {
    $data = createUserAndGenerateToken();
    $token = $data['token'];

    $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])
        ->postJson($url, [])
        ->assertStatus(422)
        ->assertSeeText('The callback url field is required');

    $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])
        ->postJson($url, [
            'callbackUrl' => 'invalid-url',
        ])
        ->assertStatus(422)
        ->assertSeeText('The callback url field must be a valid URL');

});

test('only unverified user can request verification email', function () use ($url) {
    $data = createUserAndGenerateToken();
    $token = $data['token'];
    $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])
        ->postJson($url, [
            'callbackUrl' => 'https://example.test.com',
        ])
        ->assertStatus(409)
        ->assertSeeText('User already has a verified email');

});

test('unverified user can request verification email', function () use ($url) {
    $data = createUnverifiedUserAndGenerateToken();
    $token = $data['token'];
    $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])
        ->postJson($url, [
            'callbackUrl' => 'https://example.test.com',
        ])
        ->assertStatus(200)
        ->assertSeeText('We have sent you another email verification link');

});

test('ensure verify email route exists and requires authentication', function () {
    $this->getJson('api/V1/auth/email/verify')
        ->assertStatus(401)
        ->assertSeeText('Log in to perform this action.');

});

test('ensure email verification link is valid', function () {
    $data = createUnverifiedUserAndGenerateToken();
    $token = $data['token'];
    $url = 'api/V1/auth/email/verify?expires=1753440231&hash=d9485ba98d22eaf58bc6247b142f948d5947772b&id=4&signature=692e8a71fe3660a9834722eec981fc45d8fe8b5172091c23df8da681a04ae0e7';

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])
        ->getJson($url)
        ->assertSeeText('Invalid signature.');

});

test('verify user email', function () {
    $data = createUnverifiedUserAndGenerateToken();
    $token = $data['token'];
    $user = $data['user'];

    $param = verificationUrlParam($user);
    $url = 'api/V1/auth/email/verify?'.$param;

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])
        ->getJson($url)
        ->assertSeeText('User email verified successfully!');

    $user->refresh();
    expect($user->hasVerifiedEmail())->toBe(true);
    expect($user->email_verified_at)->not()->toBe(null);

});
