<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class SocialiteController extends Controller
{
    /**
     * Validate the provider name.
     *
     * @param string $provider
     * @return bool
     */
    protected function isValidProvider(string $provider): bool
    {
        $validProviders = [
            'facebook', 'twitter', 'google', 'github', 'linkedin-openid',
            'gitlab', 'bitbucket', 'slack',
        ];
        return in_array($provider, $validProviders);
    }

    /**
     * Redirect the user to the social provider's authentication page.
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function redirectToProvider(string $provider)
    {
        if (!$this->isValidProvider($provider)) {
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage("Invalid social provider: {$provider}")
                ->build();
        }

        try {
            $driver = Socialite::driver($provider);

            if ($provider === 'google') {
                $driver->scopes(['openid', 'profile', 'email']);
            } elseif ($provider === 'linkedin-openid') {
                $driver->scopes(['openid', 'profile', 'email']);
            } elseif ($provider === 'twitter') {
                // For X (Twitter), if you need email, ensure it's enabled in your App settings.
                // Socialite's 'twitter' driver (OAuth 1.0a) typically handles it if permitted.
                // For Twitter API v2 (OAuth 2.0), you might need 'twitter-oauth-2' driver
                // (requires a separate package like socialite-providers/twitter-oauth-2)
                // and scopes like ['tweet.read', 'users.read', 'offline.access'].
            } elseif ($provider === 'slack') {
                 // Common Slack scopes: 'users:read.email', 'users:read', 'team:read'
                 // Scopes depend on what information your app needs.
                 $driver->scopes(['users:read.email', 'users:read']);
            } elseif ($provider === 'slack-openid') {
                // OpenID Connect for Slack: 'openid', 'profile', 'email'
                $driver->scopes(['openid', 'profile', 'email']);
            }

            return $driver->redirect();
        } catch (Exception $e) {
            Log::error("Socialite redirect failed for {$provider}: " . $e->getMessage());
            return ResponseBuilder::asError(500)
                ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withMessage("Could not redirect to {$provider}. Please try again.")
                ->build();
        }
    }

    /**
     * Handle the callback from the social provider.
     *
     * @param string $provider
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function handleProviderCallback(string $provider, Request $request)
    {
        if (!$this->isValidProvider($provider)) {
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage("Invalid social provider: {$provider}")
                ->build();
        }

        // Handle errors or user cancellations from the provider
        if ($request->has('error') || $request->has('denied')) {
            Log::warning("Socialite callback error from {$provider}: " . ($request->input('error_description') ?? $request->input('error') ?? 'User denied access.'));
            return ResponseBuilder::asError(401)
                ->withHttpCode(Response::HTTP_UNAUTHORIZED)
                ->withMessage("Authentication with {$provider} failed or was cancelled.")
                ->build();
        }

        try {
            // Get user information from the provider
            $socialUser = Socialite::driver($provider)->user();

            // Validate essential data from social provider
            $validator = Validator::make([
                'id' => $socialUser->getId(),
                'email' => $socialUser->getEmail(),
            ], [
                'id' => ['required', 'string'],
                'email' => ['required', 'email'], // Note: Some providers (like Twitter) might not provide email.
                                                  // Adjust this based on your provider's capabilities or fallback logic.
            ]);

            if ($validator->fails()) {
                Log::warning("Essential data missing from {$provider} user: " . json_encode($validator->errors()->toArray()));
                return ResponseBuilder::asError(422)
                    ->withHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->withMessage("Could not retrieve essential information (ID or Email) from {$provider}. Please try a different login method.")
                    ->build();
            }

            // Find or create a user in your database
            $user = User::where('provider_name', $provider)
                        ->where('provider_id', $socialUser->getId())
                        ->first();

            if (!$user) {
                // If user with this provider_id doesn't exist, check by email
                $user = User::where('email', $socialUser->getEmail())->first();

                if ($user) {
                    // User exists with this email but not linked to this social account.
                    // Option 1: Link the social account to the existing user.
                    // This is ideal for allowing users to add multiple login methods.
                    $user->provider_name = $provider;
                    $user->provider_id = $socialUser->getId();
                    $user->avatar = $socialUser->getAvatar();
                    $user->save();
                    Log::info("Linked {$provider} account to existing user: {$user->email}");

                    // Option 2 (Alternative/Optional): Prevent linking directly.
                    // If you want to force users to manually link accounts from their profile settings
                    // or require a password confirmation, you might throw an error here
                    // or redirect them to a specific linking page on the frontend.
                    // For API, you'd return an error indicating a conflict.
                    // Example: throw new Exception("An account with this email already exists. Please log in with your password to link your social account.");
                } else {
                    // No existing user, create a new one.
                    $user = User::create([
                        'first_name' => $socialUser->getName() ? explode(' ', $socialUser->getName(), 2)[0] : 'User',
                        'last_name' => $socialUser->getName() ? (isset(explode(' ', $socialUser->getName(), 2)[1]) ? explode(' ', $socialUser->getName(), 2)[1] : '') : '',
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

            // Log the user in (API token creation)
            $token = $user->createToken('SocialAuthToken')->accessToken;

            // Redirect to your frontend application's success URL, passing the token.
            // This is crucial for SPA/mobile app integration.
            $frontendSuccessUrl = config('app.frontend_url') . '/auth/social/callback?token=' . $token . '&user_id=' . $user->id;

            return redirect()->to($frontendSuccessUrl);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // This usually means an invalid token or revoked access from the provider
            Log::error("Socialite Guzzle ClientException for {$provider}: " . $e->getMessage() . " Response: " . $e->getResponse()->getBody()->getContents());
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage("Error communicating with {$provider}. Please try again.")
                ->build();
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            // This usually means a CSRF mismatch. Common if user opens multiple tabs or takes too long.
            Log::warning("Socialite InvalidStateException for {$provider}: " . $e->getMessage());
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage("Authentication session expired or invalid. Please try again.")
                ->build();
        } catch (Exception $e) {
            Log::error("Socialite callback failed for {$provider}: " . $e->getMessage());
            return ResponseBuilder::asError(500)
                ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withMessage("An unexpected error occurred during {$provider} authentication. Please try again.")
                ->build();
        }
    }
}
