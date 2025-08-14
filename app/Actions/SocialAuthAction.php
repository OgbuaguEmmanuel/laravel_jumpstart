<?php

namespace App\Actions;

use App\Enums\ActivityLogTypeEnum;
use App\Exceptions\SocialAuthException;
use App\Models\User;
use App\Notifications\LoginAlertNotification;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthAction
{
    protected array $validProviders = [
        'facebook', 'google', 'github', 'linkedin-openid',
        'gitlab', 'bitbucket', 'slack', 'twitter-oauth-2',
    ];

    public function handleRedirect($provider)
    {
        $ip = request()->ip();

        if (! $this->isValidProvider($provider)) {
            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy(null)
                ->withProperties([
                    'provider_attempted' => $provider,
                    'ip_address' => $ip,
                    'reason' => 'Invalid social provider requested',
                ])
                ->log("Attempted social login with invalid provider: {$provider}.");

            throw new SocialAuthException(
                'Invalid social provider. Allowed are: '.implode(', ', $this->validProviders),
                400,
                ['provider' => $provider, 'ip' => $ip]
            );
        }

        $driver = Socialite::driver($provider)->stateless();

        match ($provider) {
            'google', 'linkedin-openid' => $driver->scopes(['openid', 'profile', 'email']),
            'gitlab' => $driver->scopes(['read_user', 'email']),
            default => null
        };

        return $driver->redirect()->getTargetUrl();
    }

    public function handleCallback($provider, $request)
    {
        $ip = request()->ip();

        if (! $this->isValidProvider($provider)) {
            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy(null)
                ->withProperties([
                    'provider_attempted' => $provider,
                    'ip_address' => $ip,
                    'reason' => 'Invalid social provider in callback',
                ])
                ->log("Callback received for invalid social provider: {$provider}.");

            throw new SocialAuthException(
                'Invalid social provider. Allowed are: '.implode(', ', $this->validProviders),
                400,
                ['provider' => $provider, 'ip' => $ip]
            );
        }

        // Handle errors or user cancellations from the provider
        if ($request->has('error') || $request->has('denied')) {
            $errorMessage = $request->input('error_description') ?? $request->input('error') ?? 'User denied access.';
            Log::warning("Socialite callback error from {$provider}: ".$errorMessage);
            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy(null)
                ->withProperties([
                    'provider' => $provider,
                    'ip_address' => $ip,
                    'error_message' => $errorMessage,
                ])
                ->log("User denied or {$provider} authentication failed.");

            throw new SocialAuthException(
                "Authentication with {$provider} failed or was cancelled.",
                401,
                ['provider' => $provider, 'ip' => $ip, 'error' => $errorMessage]
            );
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
            Log::warning("Essential data missing from {$provider} user: ".json_encode($validator->errors()->toArray()));
            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy(null)
                ->withProperties([
                    'provider' => $provider,
                    'ip_address' => $ip,
                    'social_user_id' => $socialUser->getId(),
                    'error_details' => $validator->errors()->toArray(),
                ])
                ->log("Essential data missing from {$provider} user profile.");

            throw new SocialAuthException(
                "Could not retrieve essential information (ID or Email) from {$provider}.",
                422,
                ['errors' => $validator->errors()->toArray()]
            );
        }

        $user = User::where('provider_name', $provider)
            ->where('provider_id', $socialUser->getId())->first();

        if (! $user) {
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

                activity()
                    ->inLog(ActivityLogTypeEnum::SocialAuth)
                    ->causedBy($user)
                    ->withProperties([
                        'provider' => $provider,
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'social_user_id' => $socialUser->getId(),
                        'ip_address' => $ip,
                    ])
                    ->log("Linked {$provider} account to existing user.");
            } else {
                // No existing user, create a new one.
                $nameParts = explode(' ', $socialUser->getName(), 2);

                $user = User::create([
                    'first_name' => $nameParts[0] ?? 'User',
                    'last_name' => $nameParts[1] ?? '',
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(16)), // Assign a random password, user won't use it directly
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(), // Assume email is verified by social provider
                ]);

                Log::info("Created new user via {$provider}: {$user->email}");
                activity()
                    ->inLog(ActivityLogTypeEnum::SocialAuth)
                    ->causedBy($user)
                    ->withProperties([
                        'provider' => $provider,
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'social_user_id' => $socialUser->getId(),
                        'ip_address' => $ip,
                    ])
                    ->log("New user created via {$provider}.");
            }
        } else {
            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy($user)
                ->withProperties([
                    'provider' => $provider,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'social_user_id' => $socialUser->getId(),
                    'ip_address' => $ip,
                    'action_type' => 'Existing User Social Login',
                ])
                ->log("Existing user logged in via {$provider}.");
        }

        try {
            $token = $user->createToken('SocialAuthToken')->plainTextToken;
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'email' => $user->email,
                    'ip_address' => request()->ip(),
                    'token_created' => true,
                ])
                ->log('User logged in successfully');

            $user->notify(new LoginAlertNotification(
                $provider, request()->ip(), request()->userAgent()
            )
            );

        } catch (Exception $e) {
            logger()->error('Login failed: '.$e->getMessage(), [
                'email' => $user->email,
                'code' => $e->getCode(),
            ]);
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'email' => $user->email,
                    'error_message' => $e->getMessage(),
                    'ip_address' => request()->ip(),
                    'code' => $e->getCode(),
                ])
                ->log('Login failed: API Token creation error');

            throw new SocialAuthException(
                'Something went wrong. Please contact support.',
                500,
                ['email' => $user->email, 'code' => $e->getCode()]
            );
        }

        activity()
            ->inLog(ActivityLogTypeEnum::SocialAuth)
            ->causedBy($user)
            ->withProperties([
                'provider' => $provider,
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ip,
                'action_type' => 'Social Login Completed',
            ])
            ->log("User successfully logged in via {$provider}.");

        return [
            'user' => $user, 'token' => $token,
        ];
    }

    /**
     * Validate the provider name.
     */
    protected function isValidProvider(string $provider): bool
    {
        return in_array($provider, $this->validProviders);
    }
}
