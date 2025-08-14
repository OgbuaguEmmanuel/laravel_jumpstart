<?php

$url = '/api/V1/auth/logout';

test('logout route exists', function () use ($url) {
    $response = $this->get($url);

    $response->assertStatus(405);
});

test('user must be logged in to logout', function () use ($url) {
    $response = $this->postJson($url);

    $response->assertStatus(401);
    $response->assertJson([
        'message' => 'Log in to perform this action.',
    ]);
});

test('user can logout successfully', function () use ($url) {
    $rawToken = createUserAndGenerateToken()['token'];
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$rawToken,
        'Accept' => 'application/json',
    ])->postJson($url);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Logout Successful',
        ]);

    // Verify the token is revoked
    $protectedRouteResponse = $this->withHeaders([
        'Authorization' => 'Bearer '.$rawToken,
        'Accept' => 'application/json',
    ])->getJson('/api/V1/user');

    $protectedRouteResponse->assertStatus(401)
        ->assertJson([
            'message' => 'Log in to perform this action.',
        ]);
});
