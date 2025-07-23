<?php

test('reset password exists', function () {
    $response = $this->get('/api/auth/password-reset');

    $response->assertStatus(405);
})->only();

test('reset token is valid')
