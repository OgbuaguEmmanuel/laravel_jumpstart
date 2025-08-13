<?php

use App\Models\User;

$firstName = 'John';
$lastName = 'Doe';
$email = 'test@example.com';
$password = '@LaravPel1!@231';
$phoneNumber = '+2347012345678';
$url = '/api/V1/auth/register';

test('register route exists and is a POST method', function () use ($url) {

    $this->withoutMiddleware();

    $response = $this->get($url);

    $response->assertStatus(405);
    $response->assertSeeText('GET method is not allowed for this endpoint');

    $this->postJson($url, [])->assertStatus(422);

});

test('first name is required for registration', function () use ($lastName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'last_name' => $lastName,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'first_name' => ['The first name field is required.'],
    ]);
});

test('first name must be a string for registration', function () use ($lastName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'last_name' => $lastName,
        'first_name' => 12345,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'first_name' => ['The first name field must be a string.'],
    ]);
});

test('first name must be a minimum of 2 for registration', function () use ($lastName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'last_name' => $lastName,
        'first_name' => 'A',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'first_name' => ['The first name field must be at least 2 characters.'],
    ]);
});

test('first name must be a maximum of 100 for registration', function () use ($lastName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'last_name' => $lastName,
        'first_name' => str_repeat('A', 101),
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'first_name' => ['The first name field must not be greater than 100 characters.'],
    ]);
});


test('last name is required for registration', function () use ($firstName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'last_name' => ['The last name field is required.'],
    ]);
});

test('last name must be a string for registration', function () use ($firstName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => 12345,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'last_name' => ['The last name field must be a string.'],
    ]);
});

test('last name must be a minimum of 2 for registration', function () use ($firstName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => 'A',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'last_name' => ['The last name field must be at least 2 characters.'],
    ]);
});


test('last name must be a maximum of 100 for registration', function () use ($firstName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => str_repeat('A', 101),
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'last_name' => ['The last name field must not be greater than 100 characters.'],
    ]);
});


test('email is required for registration', function () use ($firstName, $lastName, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'email' => ['The email field is required.'],
    ]);
});

test('email must be a valid email format for registration', function () use ($firstName, $lastName, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => 'invalid-email',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'email' => ['The email field must be a valid email address.'],
    ]);
});

test('email must be unique for registration', function () use ($firstName, $lastName, $email, $password, $url) {
    User::factory()->create([
        'email' => $email
    ]);

    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'email' => ['The email has already been taken.'],
    ]);
});

test('email must be a maximum of 255 characters for registration', function () use ($firstName, $lastName, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => str_repeat('a', 256) . '@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'email' => ['The email field must not be greater than 255 characters.'],
    ]);
});


test('password is required for registration', function () use ($firstName, $lastName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password_confirmation' => $password,
    ]);
    $response->assertStatus(422);
    $response->assertJsonFragment([
        'password' => ['The password field is required.'],
    ]);
});

test('password must be a string for registration', function () use ($firstName, $lastName, $email, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 12345678,
        'password_confirmation' => 12345678,
    ]);
    $response->assertStatus(422);
    $response->assertJsonFragment([
        'password' => ['The password field must be a string.'],
    ]);
});


test('password must be confirmed for registration', function () use ($firstName, $lastName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => $password,
    ]);
    $response->assertStatus(422);
    $response->assertJsonFragment([
        'password' => ['The password field confirmation does not match.'],
    ]);
});

test('password must be a minimum of 8 characters for registration', function () use ($firstName, $lastName, $email, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 'Pass1!',
        'password_confirmation' => 'Pass1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'password' => ['The password field must be at least 8 characters.'],
    ]);
});

test('password must contain a letter for registration', function () use ($firstName, $lastName, $email, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => '2@22$£/12',
        'password_confirmation' => '2@22$£/12',
    ]);

    $response->assertStatus(422);
    $response->assertSeeText([
        'The password field must contain at least one letter.'
    ]);
});

test('password must contain at least an uppercase letter for registration', function () use ($firstName, $lastName, $email, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => '2p@22$£/12',
        'password_confirmation' => '2p@22$£/12',
    ]);

    $response->assertStatus(422);
    $response->assertSeeText([
        'The password field must contain at least one uppercase and one lowercase letter.'
    ]);
});

test('password must contain at least an lowercase letter for registration', function () use ($firstName, $lastName, $email, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => '2P@22$£/12',
        'password_confirmation' => 'P2@22$£/12',
    ]);

    $response->assertStatus(422);
    $response->assertSeeText([
        'The password field must contain at least one uppercase and one lowercase letter.'
    ]);
});

test('password must contain at least a number for registration', function () use ($firstName, $lastName, $email, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 'Password!@',
        'password_confirmation' => 'Password!@',
    ]);

    $response->assertStatus(422);
    $response->assertSeeText([
        'The password field must contain at least one number.'
    ]);
});

test('password must contain at least a symbol for registration', function () use ($firstName, $lastName, $email, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(422);
    $response->assertSeeText([
        'The password field must contain at least one symbol.'
    ]);
});

test('password must not be compromised for registration', function () use ($firstName, $lastName, $email, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
    ]);

    $response->assertStatus(422);
    $response->assertSeeText([
        'The given password has appeared in a data leak. Please choose a different password'
    ]);
});

test('there is no validation error for registration', function() use ($firstName, $lastName, $email, $password, $url, $phoneNumber) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => $password,
        'phone_number' => $phoneNumber,
        'password_confirmation' => $password,
    ]);

    expect($response->status())->not()->toBe(422);

});

test('expect to see a user created', function() use ($firstName, $lastName, $email, $password, $url, $phoneNumber) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone_number' => $phoneNumber,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $this->assertDatabaseHas('users', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
    ]);

    $this->assertDatabaseCount('users', 1);
    $this->expect(User::where('email', $email)->exists())->toBeTrue();
    $this->expect(null)->toBe($response->json('data'));
    $response->assertStatus(201);
    $response->assertSeeText('User registered successfully.');
});

test('phone number is optional for registration', function () use ($firstName, $lastName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(201);
    $response->assertSeeText('User registered successfully.');
});

test('phone number must be a valid Nigerian phone number for registration', function () use ($firstName, $lastName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone_number' => '+234-801-234-5678',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertSeeText('The phone number field format is invalid.');
});

test('phone number must be unique for registration', function () use ($firstName, $lastName, $email, $password, $url, $phoneNumber) {
    User::factory()->create([
        'phone_number' => $phoneNumber
    ]);

    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone_number' => $phoneNumber,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertSeeText('The phone number has already been taken.');
});

test('phone number must be a string for registration', function () use ($firstName, $lastName, $email, $password, $url) {
    $response = $this->postJson($url, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone_number' => 1234567890,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertSeeText('The phone number field must be a string.');
});
