<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$firstName = 'John';
$lastName = 'Doe';
$email = 'test@example.com';
$password = '@LaravPel1!@231';

test('register route exists and is a POST method', function () {

    $this->withoutMiddleware();

    $response = $this->get('/api/register');

    $response->assertStatus(405);
    $response->assertSeeText('Method Not Allowed');

    $this->postJson('/api/register', [])->assertStatus(422);

});

test('first name is required for registration', function () use ($lastName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'last_name' => $lastName,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['first_name']);
    $response->assertJsonFragment([
        'first_name' => ['The first name field is required.'],
    ]);
});

test('first name must be a string for registration', function () use ($lastName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'last_name' => $lastName,
        'first_name' => 12345,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['first_name']);
    $response->assertJsonFragment([
        'first_name' => ['The first name field must be a string.'],
    ]);
});

test('first name must be a minimum of 2 for registration', function () use ($lastName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'last_name' => $lastName,
        'first_name' => 'A',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['first_name']);
    $response->assertJsonFragment([
        'first_name' => ['The first name field must be at least 2 characters.'],
    ]);
});

test('first name must be a maximum of 100 for registration', function () use ($lastName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'last_name' => $lastName,
        'first_name' => str_repeat('A', 101),
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['first_name']);
    $response->assertJsonFragment([
        'first_name' => ['The first name field must not be greater than 100 characters.'],
    ]);
});


test('last name is required for registration', function () use ($firstName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['last_name']);
    $response->assertJsonFragment([
        'last_name' => ['The last name field is required.'],
    ]);
});

test('last name must be a string for registration', function () use ($firstName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => 12345,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['last_name']);
    $response->assertJsonFragment([
        'last_name' => ['The last name field must be a string.'],
    ]);
});

test('last name must be a minimum of 2 for registration', function () use ($firstName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => 'A',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['last_name']);
    $response->assertJsonFragment([
        'last_name' => ['The last name field must be at least 2 characters.'],
    ]);
});


test('last name must be a maximum of 100 for registration', function () use ($firstName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => str_repeat('A', 101),
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['last_name']);
    $response->assertJsonFragment([
        'last_name' => ['The last name field must not be greater than 100 characters.'],
    ]);
});


test('email is required for registration', function () use ($firstName, $lastName, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertJsonFragment([
        'email' => ['The email field is required.'],
    ]);
});

test('email must be a valid email format for registration', function () use ($firstName, $lastName, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => 'invalid-email',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertJsonFragment([
        'email' => ['The email field must be a valid email address.'],
    ]);
});

test('email must be unique for registration', function () use ($firstName, $lastName, $email, $password) {
    User::factory()->create([
        'email' => $email
    ]);

    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertJsonFragment([
        'email' => ['The email has already been taken.'],
    ]);
});

test('email must be a maximum of 255 characters for registration', function () use ($firstName, $lastName, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => str_repeat('a', 256) . '@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertJsonFragment([
        'email' => ['The email field must not be greater than 255 characters.'],
    ]);
});


test('password is required for registration', function () use ($firstName, $lastName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password_confirmation' => $password,
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertJsonFragment([
        'password' => ['The password field is required.'],
    ]);
});

test('password must be a string for registration', function () use ($firstName, $lastName, $email) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 12345678,
        'password_confirmation' => 12345678,
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertJsonFragment([
        'password' => ['The password field must be a string.'],
    ]);
});


test('password must be confirmed for registration', function () use ($firstName, $lastName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => $password,
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertJsonFragment([
        'password' => ['The password field confirmation does not match.'],
    ]);
});

test('password must be a minimum of 8 characters for registration', function () use ($firstName, $lastName, $email) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 'Pass1!',
        'password_confirmation' => 'Pass1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertJsonFragment([
        'password' => ['The password field must be at least 8 characters.'],
    ]);
});

test('password must contain a letter for registration', function () use ($firstName, $lastName, $email) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => '2@22$£/12',
        'password_confirmation' => '2@22$£/12',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The password field must contain at least one letter.'
    ]);
});

test('password must contain at least an uppercase letter for registration', function () use ($firstName, $lastName, $email) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => '2p@22$£/12',
        'password_confirmation' => '2p@22$£/12',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The password field must contain at least one uppercase and one lowercase letter.'
    ]);
});

test('password must contain at least an lowercase letter for registration', function () use ($firstName, $lastName, $email) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => '2P@22$£/12',
        'password_confirmation' => 'P2@22$£/12',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The password field must contain at least one uppercase and one lowercase letter.'
    ]);
});

test('password must contain at least a number for registration', function () use ($firstName, $lastName, $email) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 'Password!@',
        'password_confirmation' => 'Password!@',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The password field must contain at least one number.'
    ]);
});

test('password must contain at least a symbol for registration', function () use ($firstName, $lastName, $email) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The password field must contain at least one symbol.'
    ]);
});

test('password must not be compromised for registration', function () use ($firstName, $lastName, $email) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
    $response->assertSeeText([
        'The given password has appeared in a data leak. Please choose a different password'
    ]);
});

test('there is no validation error for registration', function() use ($firstName, $lastName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    expect($response->status())->not()->toBe(422);

});

test('expect to see a user created', function() use ($firstName, $lastName, $email, $password) {
    $response = $this->postJson('/api/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
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

