<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SocialAuthAction;
use App\Enums\ActivityLogTypeEnum;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the social provider's authentication page.
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function redirectToProvider(string $provider, SocialAuthAction $action)
    {
        $ipAddress = request()->ip();

        try {
            $url = $action->handleRedirect($provider);

            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy(null)
                ->withProperties([
                    'provider' => $provider,
                    'ip_address' => $ipAddress,
                    'redirect_url' => $url,
                    'action_type' => 'Social Login Redirect Initiated',
                ])
                ->log("User redirected to {$provider} for authentication.");

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withData(['url' => $url])
                ->build();
        } catch (ValidationException $e) {
            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy(null)
                ->withProperties([
                    'provider' => $provider,
                    'ip_address' => $ipAddress,
                    'error_message' => $e->getMessage(),
                    'error_details' => $e->errors(),
                    'action_type' => 'Social Login Redirect Failed: Validation Error',
                ])
                ->log("Social login redirect to {$provider} failed due to validation.");
            return ResponseBuilder::asError($e->status)
                ->withHttpCode($e->status)
                ->withMessage($e->getMessage())
                ->withData($e->errors())
                ->build();
        } catch (Exception $e) {
            Log::error("Socialite redirect failed for {$provider}: " . $e->getMessage());
            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy(null)
                ->withProperties([
                    'provider' => $provider,
                    'ip_address' => $ipAddress,
                    'error_message' => $e->getMessage(),
                    'action_type' => 'Social Login Redirect Failed: Unexpected Error',
                ])
                ->log("Social login redirect to {$provider} failed unexpectedly.");

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
    public function handleProviderCallback(string $provider, Request $request, SocialAuthAction $action)
    {
        $ipAddress = request()->ip();
        try {
            $data = $action->handleCallback($provider, $request);

            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy($data['user'])
                ->withProperties([
                    'provider' => $provider,
                    'user_id' => $data['user']->id,
                    'email' => $data['user']->email,
                    'ip_address' => $ipAddress,
                    'action_type' => 'Social Login Completed',
                ])
                ->log("User successfully logged in via {$provider}.");

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withData([
                    'token' => $data['token'],
                    'user' => $data['user']
                ])
                ->build();
        } catch (ValidationException $e) {
            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy(null)
                ->withProperties([
                    'provider' => $provider,
                    'ip_address' => $ipAddress,
                    'error_message' => $e->getMessage(),
                    'error_details' => $e->errors(),
                    'action_type' => 'Social Login Callback Failed: Validation Error',
                ])
                ->log("Social login callback from {$provider} failed due to validation.");
            return ResponseBuilder::asError($e->status)
                ->withHttpCode($e->status)
                ->withMessage($e->getMessage())
                ->withData($e->errors())
                ->build();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            Log::error("Socialite Guzzle ClientException for {$provider}: " . $e->getMessage());
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage("Error communicating with {$provider}. Please try again.")
                ->build();
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::warning("Socialite InvalidStateException for {$provider}: " . $e->getMessage());

            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy(null)
                ->withProperties([
                    'provider' => $provider,
                    'ip_address' => $ipAddress,
                    'error_message' => $e->getMessage(),
                    'http_status' => $e->getCode(),
                    'action_type' => 'Social Login Callback Failed: Provider API Error',
                ])
                ->log("Social login callback from {$provider} failed due to provider API error.");
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage("Authentication session expired or invalid. Please try again.")
                ->build();
        } catch (Exception $e) {
            Log::error("Socialite callback failed for {$provider}: " . $e->getMessage());
            activity()
                ->inLog(ActivityLogTypeEnum::SocialAuth)
                ->causedBy(null)
                ->withProperties([
                    'provider' => $provider,
                    'ip_address' => $ipAddress,
                    'error_message' => $e->getMessage(),
                    'action_type' => 'Social Login Callback Failed: Invalid State',
                ])
                ->log("Social login callback from {$provider} failed due to invalid state.");

            return ResponseBuilder::asError(500)
                ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withMessage("An unexpected error occurred during {$provider} authentication.")
                ->build();
        }
    }
}
