<?php

namespace App\Actions;

use App\Traits\AuthHelpers;
use Illuminate\Support\Facades\Hash;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class LoginUserAction
{
    use AuthHelpers;

    /**
     * Create a new class instance.
     */
    public function __construct(array $data)
    {
        $user = $this->getUserByEmail($data['email']);

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return ResponseBuilder::asError(404)
                ->withMessage('Invalid Login credentials')
                ->build();
        }

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;
        $userDetails = $user->only('id', 'first_name', 'last_name', 'email', 'created_at');

        return [
            'token' => $token,
            'user' => $userDetails,
        ];
    }
}
