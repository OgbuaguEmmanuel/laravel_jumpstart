<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SocialAuthAction;
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
        try {
            $url = $action->handleRedirect($provider);

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withData(['url' => $url])
                ->build();
        } catch (ValidationException $e) {
            return ResponseBuilder::asError($e->status)
                ->withHttpCode($e->status)
                ->withMessage($e->getMessage())
                ->withData($e->errors())
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
    public function handleProviderCallback(string $provider, Request $request, SocialAuthAction $action)
    {
        try {
            $data = $action->handleCallback($provider, $request);

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withData([
                    'token' => $data['token'],
                    'user' => $data['user']
                ])
                ->build();
        } catch (ValidationException $e) {
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
