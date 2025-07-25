<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

$url = '/api/auth/change-password';

test('route exists', function () use ($url) {
    $response = $this->get($url);

    $response->assertStatus(405);
});

test('ensure user is authenticated', function () use ($url) {
    $response = $this->postJson($url, [
        'current_password' => 'currentpassword123',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(401);
});

beforeEach(function () {
    Artisan::call('passport:client', [
        '--personal' => true,
        '--name' => 'Test Personal Access Client',
        '--provider' => 'users',
    ]);
});

test('ensure current password is required', function () use ($url) {
    $rawToken = createUserAndGenerateToken()['token'];

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rawToken,
        'Accept' => 'application/json'
    ])
    ->postJson($url, [
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);
});

test('ensure new password is required', function () use ($url) {
    $rawToken = createUserAndGenerateToken()['token'];
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rawToken,
        'Accept' => 'application/json'
    ])
    ->postJson($url, [
        'current_password' => 'currentpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['new_password']);
});

test('ensure new password confirmation is required', function () use ($url) {
    $rawToken = createUserAndGenerateToken()['token'];
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rawToken,
        'Accept' => 'application/json'
    ])
    ->postJson($url, [
        'current_password' => 'currentpassword123',
        'new_password' => 'newpassword123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['new_password'])
        ->assertSeeText('The new password field confirmation does not match.');
});

test('ensure new password matches confirmation', function () use ($url) {
    $rawToken = createUserAndGenerateToken()['token'];
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rawToken,
        'Accept' => 'application/json'
    ])
    ->postJson($url, [
        'current_password' => 'currentpassword123',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'differentpassword123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['new_password']);
});

test('ensure current password is correct and password validations passes', function () use ($url) {
    $rawToken = createUserAndGenerateToken()['token'];
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rawToken,
        'Accept' => 'application/json'
    ])
    ->postJson($url, [
        'current_password' => 'wrongpassword',
        'new_password' => 'chiPassword@123',
        'new_password_confirmation' => 'chiPassword@123',
    ]);

    $response->assertStatus(422)
        ->assertSeeText('Current password is incorrect');
});


test('change password with correct details', function () use ($url) {
    $newPassword = 'chiPassword@123';
    $data = createUserAndGenerateToken();
    $rawToken = $data['token'];
    $user = $data['user'];

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rawToken,
        'Accept' => 'application/json'
    ])
    ->postJson($url, [
        'current_password' => 'password',
        'new_password' => $newPassword,
        'new_password_confirmation' => $newPassword,
    ]);

    $user->refresh();

    $response->assertStatus(200)
        ->assertSeeText('Password changed successfully');

   expect(Hash::check($newPassword, $user->password))->toBe(true);
});
