<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthAction
{
    public function handleRedirect($provider)
    {
        if (!$this->isValidProvider($provider)) {
            throw ValidationException::withMessages([
                'provider' => "Invalid social provider: {$provider}"
            ])->status(400);
        }

        $driver = Socialite::driver($provider)->stateless();

        // Set provider-specific scopes
        switch ($provider) {
            case 'google':
                $driver->scopes(['openid', 'profile', 'email']);
                break;
            case 'linkedin-openid':
                $driver->scopes(['openid', 'profile', 'email']);
                break;
            case 'gitlab':
                $driver->scopes(['read_user', 'email']);
            break;
        }
        return $driver->redirect()->getTargetUrl();
    }

    public function handleCallback($provider, $request)
    {
        if (!$this->isValidProvider($provider)) {
            throw ValidationException::withMessages([
                'provider' => "Invalid social provider: {$provider}"
            ])->status(400);
        }

        // Handle errors or user cancellations from the provider
        if ($request->has('error') || $request->has('denied')) {
            Log::warning("Socialite callback error from {$provider}: " . ($request->input('error_description') ?? $request->input('error') ?? 'User denied access.'));
            throw ValidationException::withMessages([
                'provider' => "Authentication with {$provider} failed or was cancelled."
            ])->status(401);
        }

        // Get user information from the provider
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $validator = Validator::make([
            'id' => (string) $socialUser->getId(),
            'email' => $socialUser->getEmail(),
        ], [
            'id' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            Log::warning("Essential data missing from {$provider} user: " . json_encode($validator->errors()->toArray()));
            throw ValidationException::withMessages([
                'provider' => "Could not retrieve essential information (ID or Email) from {$provider}. Please try a different login method."
            ])->status(422);
        }

        $user = User::where('provider_name', $provider)
            ->where('provider_id', $socialUser->getId())->first();

        if (!$user) {
            // If user with this provider_id doesn't exist, check by email
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // User exists with this email but not linked to this social account.
                $user->update([
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
                Log::info("Linked {$provider} account to existing user: {$user->email}");

            } else {
                // No existing user, create a new one.
                $nameParts = explode(' ', $socialUser->getName(), 2);

                $user = User::create([
                    'first_name' =>$nameParts[0] ?? 'User',
                    'last_name' => $nameParts[1] ?? '',
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(16)), // Assign a random password, user won't use it directly
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(), // Assume email is verified by social provider
                ]);
                Log::info("Created new user via {$provider}: {$user->email}");
            }
        }

        $token = $user->createToken('SocialAuthToken')->accessToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Validate the provider name.
     *
     * @param string $provider
     * @return bool
     */
    protected function isValidProvider(string $provider): bool
    {
        $validProviders = [
            'facebook', 'google', 'github', 'linkedin-openid',
            'gitlab', 'bitbucket', 'slack','twitter-oauth-2'
        ];
        return in_array($provider, $validProviders);
    }
}
