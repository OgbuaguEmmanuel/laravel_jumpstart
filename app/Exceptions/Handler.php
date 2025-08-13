<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Lang;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if($request->expectsJson() || $request->routeIs('api.*')){
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }


    public function handleApiException(Request $request, $exception){

        if($exception instanceof MethodNotAllowedHttpException){
            return ResponseBuilder::asError(Response::HTTP_METHOD_NOT_ALLOWED)
                ->withHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->withMessage(strtoupper($request->getMethod()) . ' method is not allowed for this endpoint.')
                ->build();
        }

        if($exception instanceof NotFoundHttpException){
            return ResponseBuilder::asError(Response::HTTP_NOT_FOUND)
                ->withHttpCode(Response::HTTP_NOT_FOUND)
                ->withMessage($exception->getMessage())
                ->build();
        }

        if($exception instanceof RouteNotFoundException){
            return ResponseBuilder::asError(Response::HTTP_NOT_FOUND)
                ->withHttpCode(Response::HTTP_NOT_FOUND)
                ->withMessage($exception->getMessage())
                ->build();
        }

        if($exception instanceof AuthorizationException){
            return ResponseBuilder::asError(Response::HTTP_FORBIDDEN)
                ->withHttpCode(Response::HTTP_FORBIDDEN)
                ->withMessage($exception->getMessage())
                ->build();
        }

        if($exception instanceof AuthenticationException){
            return ResponseBuilder::asError(Response::HTTP_UNAUTHORIZED)
                ->withHttpCode(Response::HTTP_UNAUTHORIZED)
                ->withMessage('Log in to perform this action.')
                ->build();
        }

        if($exception instanceof ModelNotFoundException){
            return ResponseBuilder::asError(Response::HTTP_NOT_FOUND)
                ->withHttpCode(Response::HTTP_NOT_FOUND)
                ->withMessage('No query result was found for the resource.')
                ->build();
        }

        if($exception instanceof ThrottleRequestsException){
            return ResponseBuilder::asError(Response::HTTP_TOO_MANY_REQUESTS)
                ->withHttpCode(Response::HTTP_TOO_MANY_REQUESTS)
                ->withMessage(Lang::get('auth.throttle'))
                ->build();
        }

        if($exception instanceof BadRequestException){
            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage('Bad request.')
                ->build();
        }

        if($exception instanceof ValidationException){
            return ResponseBuilder::asError($exception->status)
                ->withHttpCode($exception->status)
                ->withData($exception->errors())
                ->withMessage($exception->getMessage())
                ->build();
        }

        if(method_exists($exception, 'getStatusCode')){
            return ResponseBuilder::asError($exception->getStatusCode)
                ->withHttpCode($exception->getStatusCode)
                ->withMessage($exception->getMessage())
                ->build();
        }

        if ($exception instanceof SocialAuthException) {
            return ResponseBuilder::asError($exception->getCode() ?: Response::HTTP_BAD_REQUEST)
                ->withHttpCode($exception->getCode() ?: Response::HTTP_BAD_REQUEST)
                ->withMessage($exception->getMessage())
                ->withData($exception->getContext())
                ->build();
        }

        if ($exception instanceof HttpException) {
            $safeMessage = app()->environment('production')
                ? __('Something went wrong. Please try again.')
                : $exception->getMessage(); // Show raw only in dev/test

            return ResponseBuilder::asError($exception->getStatusCode())
                ->withMessage($safeMessage)
                ->build();

        }

        return ResponseBuilder::asError(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->withMessage('A technical error occurred.')
            ->build();
    }

}
