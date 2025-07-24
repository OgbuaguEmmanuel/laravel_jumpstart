<?php

namespace App\Traits;

use App\Models\User;

trait AuthHelpers
{
    protected function maskEmailAddress($email)
    {
        // Find the position of '@' in the email
        $atPosition = strpos($email, '@');

        // Keep the first three characters and mask the rest before '@'
        $maskedUsername = substr($email, 0, 3).str_repeat('*', $atPosition - 2).substr($email, $atPosition);

        return $maskedUsername;
    }

    protected function maskPhoneNumber($phoneNumber)
    {
        $maskedNumber = substr_replace($phoneNumber, str_repeat('*', 5), 4, 5);

        return $maskedNumber;
    }

    protected function getUserByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    protected function getUserByID($id)
    {
        return User::find($id);
    }
}
