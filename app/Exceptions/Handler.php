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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
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
            return Response::error(
                [],
                'The ' . strtoupper($request->method()) . ' is not allowed for this endpoint.',
                Response::HTTP_METHOD_NOT_ALLOWED
            );
        }

        if($exception instanceof NotFoundHttpException){
            return Response::error([], $exception->getMessage(), Response::HTTP_NOT_FOUND);
        }

        if($exception instanceof RouteNotFoundException){
            return Response::error([], $exception->getMessage(), Response::HTTP_NOT_FOUND);
        }

        if($exception instanceof AuthorizationException){
            return Response::error([], $exception->getMessage(), Response::HTTP_FORBIDDEN);
        }

        if($exception instanceof AuthenticationException){
            return Response::error([], "You need to login to perform this action.", Response::HTTP_UNAUTHORIZED);
        }

        if($exception instanceof ModelNotFoundException){
            return Response::error([], 'No query result was found for the resource.', Response::HTTP_NOT_FOUND);
        }

        if($exception instanceof ThrottleRequestsException){
            return Response::error([], Lang::get('auth.throttle'), Response::HTTP_TOO_MANY_REQUESTS);
        }

        if($exception instanceof BadRequestException){
            return Response::error([], 'Bad request.', Response::HTTP_BAD_REQUEST);
        }

        if($exception instanceof ValidationException){
            return Response::error(['errors' => $exception->errors()], $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if(method_exists($exception, 'getStatusCode')){
            return Response::error([], $exception->getMessage(), $exception->getStatusCode());
        }

        return Response::error([], 'A technical error occurred.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

}
