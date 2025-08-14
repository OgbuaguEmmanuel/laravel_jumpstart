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

    protected function generateRandomPassword(int $length = 12): string
    {
        // Ensure minimum length
        if ($length < 8) {
            $length = 8;
        }

        // Define character pools
        $lowerCaseChars = 'abcdefghijklmnopqrstuvwxyz';
        $upperCaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numberChars = '0123456789';
        $symbolChars = '!@#$%^&*()-_=+[]{}|;:,.<>?'; // Common symbols

        $allChars = $lowerCaseChars.$upperCaseChars.$numberChars.$symbolChars;

        $password = [];

        // Ensure at least one of each required type
        $password[] = $lowerCaseChars[random_int(0, strlen($lowerCaseChars) - 1)];
        $password[] = $upperCaseChars[random_int(0, strlen($upperCaseChars) - 1)];
        $password[] = $numberChars[random_int(0, strlen($numberChars) - 1)];
        $password[] = $symbolChars[random_int(0, strlen($symbolChars) - 1)];

        // Fill the rest of the password length with random characters from all pools
        for ($i = 0; $i < ($length - 4); $i++) { // -4 because we've already added 4 required types
            $password[] = $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the password array to randomize the order of characters
        shuffle($password);

        return implode('', $password);
    }
}
