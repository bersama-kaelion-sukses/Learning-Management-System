<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Validation\ValidationException;
use Throwable;

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
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof AuthenticationException) {
            return redirect()->route('login');
        }

        if (
            $exception instanceof AuthorizationException ||
            ($exception instanceof HttpExceptionInterface &&
            $exception->getStatusCode() === 403)
        ) {
            return response()->view(
                'errors.generic',
                [],
                $statusCode
            );
        }

        if ($exception instanceof ValidationException) {
            return parent::render($request, $exception);
        }

        if (!config('app.debug')) {

            $statusCode = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The service is currently unavailable. Please try again later.'
                ], $statusCode);
            }

            return response()->view(
                'errors.generic',
                [],
                $statusCode
            );
        }

        return parent::render($request, $exception);
    }


    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Always redirect to login no matter the request type (avoid JSON leak)
        return redirect()->guest(route('login')); // ganti sesuai route login kamu
    }
}
