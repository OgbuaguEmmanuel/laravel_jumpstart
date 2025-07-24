<?php

test('reset password exists', function () {
    $response = $this->get('/api/auth/password-reset');

    $response->assertStatus(405);
});

