<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\SocialAuthException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

final class APIExceptionHandler
{
    public function handle(
        Request $request,
        Throwable $throwable
    ): Response {
        if ($throwable instanceof MethodNotAllowedException
            || $throwable instanceof MethodNotAllowedHttpException
        ) {
            return ResponseBuilder::asError(Response::HTTP_METHOD_NOT_ALLOWED)
                ->withHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->withMessage(strtoupper($request->getMethod()).' method is not allowed for this endpoint.')
                ->build();
        }

        if ($throwable instanceof NotFoundHttpException
            || $throwable instanceof RouteNotFoundException
            || $throwable instanceof ModelNotFoundException
            || $throwable instanceof FileNotFoundException
        ) {
            $message = $throwable->getMessage();

            if (empty($message)) {
                $message = 'Not found.';
            }

            return ResponseBuilder::asError(Response::HTTP_NOT_FOUND)
                ->withHttpCode(Response::HTTP_NOT_FOUND)
                ->withMessage($message)
                ->build();
        }

        if ($throwable instanceof AuthorizationException) {
            return ResponseBuilder::asError(Response::HTTP_FORBIDDEN)
                ->withHttpCode(Response::HTTP_FORBIDDEN)
                ->withMessage($throwable->getMessage())
                ->build();
        }

        if ($throwable instanceof AuthenticationException) {
            return ResponseBuilder::asError(Response::HTTP_UNAUTHORIZED)
                ->withHttpCode(Response::HTTP_UNAUTHORIZED)
                ->withMessage('Log in to perform this action.')
                ->build();
        }

        if ($throwable instanceof ThrottleRequestsException) {
            $message = $throwable->getMessage();

            if (empty($message)) {
                $message = 'Max number of attempts exceeded. Please wait for some seconds.';
            }

            return ResponseBuilder::asError(Response::HTTP_TOO_MANY_REQUESTS)
                ->withHttpCode(Response::HTTP_TOO_MANY_REQUESTS)
                ->withMessage($message)
                ->build();
        }

        if ($throwable instanceof BadRequestException
            || $throwable instanceof BadRequestHttpException
        ) {
            $message = $throwable->getMessage();

            if (empty($message)) {
                $message = 'Bad request.';
            }

            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage($message)
                ->build();
        }

        if ($throwable instanceof ValidationException) {
            return ResponseBuilder::asError($throwable->status)
                ->withHttpCode($throwable->status)
                ->withData($throwable->errors())
                ->withMessage($throwable->getMessage())
                ->build();
        }

        if ($throwable instanceof AccessDeniedHttpException) {
            return ResponseBuilder::asError(Response::HTTP_FORBIDDEN)
                ->withHttpCode(Response::HTTP_FORBIDDEN)
                ->withMessage($throwable->getMessage())
                ->build();
        }

        if ($throwable instanceof SocialAuthException) {
            return ResponseBuilder::asError($throwable->getCode() ?: Response::HTTP_BAD_REQUEST)
                ->withHttpCode($throwable->getCode() ?: Response::HTTP_BAD_REQUEST)
                ->withMessage($throwable->getMessage())
                ->withData($throwable->getContext())
                ->build();
        }

        if ($throwable instanceof PostTooLargeException) {
            return ResponseBuilder::asError(Response::HTTP_REQUEST_ENTITY_TOO_LARGE)
                ->withHttpCode(Response::HTTP_REQUEST_ENTITY_TOO_LARGE)
                ->withMessage('Uploaded file is too large.')
                ->build();
        }

        if ($throwable instanceof QueryException) {
            $message = app()->environment('production')
                ? __('A database error occurred. Please try again later.')
                : $throwable->getMessage();

            return ResponseBuilder::asError(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withMessage($message)
                ->build();
        }

        if ($throwable instanceof BindingResolutionException) {
            $safeMessage = app()->environment('production')
                ? __('Something went wrong while preparing your request. Please try again.')
                : $throwable->getMessage();

            return ResponseBuilder::asError(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withMessage($safeMessage)
                ->build();
        }

        if ($throwable instanceof FileException) {
            return ResponseBuilder::asError(Response::HTTP_BAD_REQUEST)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage('There was an error processing your file.')
                ->build();
        }

        if ($throwable instanceof \TypeError || $throwable instanceof \ErrorException) {
            return ResponseBuilder::asError(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withMessage(app()->environment('production')
                    ? 'Internal server error.'
                    : $throwable->getMessage())
                ->build();
        }

        if ($throwable instanceof HttpException) {
            $safeMessage = app()->environment('production')
                ? __('Something went wrong. Please try again.')
                : $throwable->getMessage(); // Show raw only in dev/test

            return ResponseBuilder::asError($throwable->getStatusCode())
                ->withMessage($safeMessage)
                ->build();
        }

        if (method_exists($throwable, 'getStatusCode')) {
            return ResponseBuilder::asError($throwable->getStatusCode)
                ->withHttpCode($throwable->getStatusCode)
                ->withMessage($throwable->getMessage())
                ->build();
        }

        return ResponseBuilder::asError(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->withMessage('An error occurred. Please try again.')
            ->build();
    }
}
