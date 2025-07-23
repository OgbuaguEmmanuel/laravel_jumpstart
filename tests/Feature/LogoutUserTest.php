<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;

test('logout route exists', function () {
    $response = $this->get('/api/auth/logout');

    $response->assertStatus(405);
});

test('user must be logged in to logout', function () {
    $response = $this->postJson('/api/auth/logout');

    $response->assertStatus(401);
    $response->assertJson([
            'message' => 'Unauthenticated.'
        ]);
});

beforeEach(function () {
    Artisan::call('passport:client', [
        '--personal' => true,
        '--name' => 'Test Personal Access Client',
        '--provider' => 'users',
    ]);
});

test('user can logout successfully', function () {
    $user = User::factory()->create();

    $personalAccessTokenResult = $user->createToken('TestToken');
    $rawToken = $personalAccessTokenResult->accessToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rawToken,
        'Accept' => 'application/json'
    ])->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Logout Successful'
        ]);

    // Verify the token is revoked
    $protectedRouteResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $rawToken,
        'Accept' => 'application/json',
    ])->getJson('/api/user');

    $protectedRouteResponse->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.'
        ]);
});
