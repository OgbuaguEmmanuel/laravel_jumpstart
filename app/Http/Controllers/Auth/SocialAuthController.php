<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
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
            'facebook', 'google', 'github', 'linkedin-openid',
            'gitlab', 'bitbucket', 'slack','x'
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

            $url = $driver->redirect()->getTargetUrl();

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withData(['url' => $url])
                ->build();
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
                return ResponseBuilder::asError(422)
                    ->withHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->withMessage("Could not retrieve essential information (ID or Email) from {$provider}. Please try a different login method.")
                    ->build();
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

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withData([
                    'token' => $token,
                    'user' => $user
                ])
                ->build();

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            Log::error("Socialite Guzzle ClientException for {$provider}: " . $e->getMessage());
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage("Error communicating with {$provider}. Please try again.")
                ->build();
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::warning("Socialite InvalidStateException for {$provider}: " . $e->getMessage());
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage("Authentication session expired or invalid. Please try again.")
                ->build();
        } catch (Exception $e) {
            Log::error("Socialite callback failed for {$provider}: " . $e->getMessage());
            return ResponseBuilder::asError(500)
                ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withMessage("An unexpected error occurred during {$provider} authentication.")
                ->build();
        }
    }
}
