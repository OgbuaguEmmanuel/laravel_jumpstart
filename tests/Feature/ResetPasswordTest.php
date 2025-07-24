<?php

$url = '/api/auth/password-reset';
$password = '@LaravPel1!@231';

test('reset password exists', function () use ($url) {
    $response = $this->get($url);

    $response->assertStatus(405);
});

test('token is required', function () use ($url) {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url, []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token'])
        ->assertSeeText('The token field is required.');
});

test('email is required', function () use ($url) {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url, []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email'])
        ->assertSeeText('The email field is required.');
});

test('email is valid', function () use ($url) {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url, ['email' => 'invalid-email']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email'])
        ->assertSeeText('The email field must be a valid email address.');
});

test('email must exist in users table', function () use ($url) {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url,[
        'email' => 'example@test.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email'])
        ->assertSeeText('The selected email is invalid.');
});

test('password is required to reset', function () use ($password, $url) {
    $response = $this->postJson($url, [
        'password_confirmation' => $password,
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText('The password field is required.');
});

test('password must be a string', function () use ($url) {
    $response = $this->postJson($url, [
        'password' => 12345678,
        'password_confirmation' => 12345678,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText('The password field must be a string.');
});


test('password must be confirmed to reset', function () use ($password, $url) {
    $response = $this->postJson($url, [
        'password' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText('The password field confirmation does not match.');
});

test('password must be a minimum of 8 characters to reset', function () use ($url) {
    $response = $this->postJson($url, [
        'password' => 'Pass1!',
        'password_confirmation' => 'Pass1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText('The password field must be at least 8 characters.');
});

test('password must contain a letter to reset', function () use ($url) {
    $response = $this->postJson($url, [
        'password' => '2@22$£/12',
        'password_confirmation' => '2@22$£/12',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The password field must contain at least one letter.'
    ]);
});

test('password must contain at least an uppercase letter to reset', function () use ($url) {
    $response = $this->postJson($url, [
        'password' => '2p@22$£/12',
        'password_confirmation' => '2p@22$£/12',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The password field must contain at least one uppercase and one lowercase letter.'
    ]);
});

test('password must contain at least an lowercase letter to reset', function () use ($url) {
    $response = $this->postJson($url, [
        'password' => '2P@22$£/12',
        'password_confirmation' => 'P2@22$£/12',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The password field must contain at least one uppercase and one lowercase letter.'
    ]);
});

test('password must contain at least a number to reset', function () use ($url) {
    $response = $this->postJson($url, [
        'password' => 'Password!@',
        'password_confirmation' => 'Password!@',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The password field must contain at least one number.'
    ]);
});

test('password must contain at least a symbol to reset', function () use ($url) {
    $response = $this->postJson($url, [
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The password field must contain at least one symbol.'
    ]);
});

test('password must not be compromised to reset', function () use ($url) {
    $response = $this->postJson($url, [
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The given password has appeared in a data leak. Please choose a different password'
    ]);
});

test('callback Contact Url is valid', function() use ($url) {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url, [
        'callbackContactUrl' => 'invalid-url'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['callbackContactUrl'])
        ->assertSeeText('The callback contact url field must be a valid URL.');
});

test('callbackContactUrl is required', function() use ($url) {

    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])
    ->postJson($url, []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['callbackContactUrl'])
        ->assertSeeText('The callback contact url field is required.');
});

test('can reset password with valid data', function () use ($url, $password) {
    $user = \App\Models\User::factory()->create(['email_verified_at' => null]);
    $token = \Illuminate\Support\Facades\Password::broker('users')->createToken($user);

    $response = $this->postJson($url, [
        'email' => $user->email,
        'callbackContactUrl' => 'https://example.com/callback',
        'token' => $token,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(200)
        ->assertSeeText('Your password has been reset.');
    $this->assertTrue(\Illuminate\Support\Facades\Hash::check($password, $user->fresh()->password));

});
