<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
    ): JsonResponse {
        if ($throwable instanceof MethodNotAllowedException
            || $throwable instanceof MethodNotAllowedHttpException
        ) {
            return ResponseBuilder::asError(Response::HTTP_METHOD_NOT_ALLOWED)
                ->withHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->withMessage(strtoupper($request->getMethod()) . ' method is not allowed for this endpoint.')
                ->build();
        }

        if ($throwable instanceof NotFoundHttpException
            || $throwable instanceof RouteNotFoundException
            || $throwable instanceof ModelNotFoundException
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
                $message = "Max number of attempts exceeded. Please wait for some seconds.";
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
                $message = "Bad request.";
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

        if (method_exists($throwable, 'getStatusCode')) {
            return ResponseBuilder::asError($throwable->getStatusCode)
                ->withHttpCode($throwable->getStatusCode)
                ->withMessage($throwable->getMessage())
                ->build();
        }

        return ResponseBuilder::asError(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->withHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->withMessage("An error occurred. Please try again.")
            ->build();
    }
}
