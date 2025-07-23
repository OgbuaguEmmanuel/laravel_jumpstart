<?php

namespace App\Actions;

use App\Traits\AuthHelpers;
use Exception;
use Illuminate\Support\Facades\Hash;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class LoginUserAction
{
    use AuthHelpers;

    /**
     * Create a new class instance.
     */
    public function __invoke(array $data)
    {
        $user = $this->getUserByEmail($data['email']);

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new Exception("Invalid Login Credential", 404);
        }

        try {
            $token = $user->createToken('UserAuthToken')->accessToken;
        } catch (Exception $e) {
            logger()->error('Login failed: ' . $e->getMessage(), [
                'email' => $data['email'],
                'code' => $e->getCode(),
            ]);
            throw new Exception("Something went wrong. Please contact support", 500);
        }

        $userDetails = $user->only('id', 'first_name', 'last_name', 'email', 'created_at');

        return [
            'token' => $token,
            'user' => $userDetails,
        ];
    }
}
