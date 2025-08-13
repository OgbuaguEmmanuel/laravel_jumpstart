<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SocialAuthAction;
use App\Enums\ActivityLogTypeEnum;
use App\Helpers\APIExceptionHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SocialAuthController extends Controller
{
    protected APIExceptionHandler $apiExceptionHandler;

    public function __construct(APIExceptionHandler $apiExceptionHandler)
    {
        $this->apiExceptionHandler = $apiExceptionHandler;
    }

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
        } catch (Throwable $th) {
            Log::error("Socialite redirect failed for {$provider}: " . $th->getMessage());

            return $this->apiExceptionHandler->handle(request(), $th);
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
        } catch (Throwable $th) {
            Log::error("Socialite callback failed for {$provider}: " . $th->getMessage());
            return $this->apiExceptionHandler->handle($request, $th);
        }


    }
}
