<?php

namespace App\Exceptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {

        // Jika request menginginkan JSON atau berasal dari route API
        if ($request->isJson() || $request->is('api/*')) {
            $status = 500;
            $message = 'Server Error';

            if ($exception instanceof HttpException) {
                $status = $exception->getStatusCode();
                $message = $exception->getMessage() ?: Response::$statusTexts[$status] ?? 'Error';
            }

            return response()->json(['message' => $message], $status);
        }

        // Default behavior jika bukan JSON request
        return response()->json([
            'message' => 'Forbidden'
        ], 403);

        // if ($request->expectsJson()) {

        //     if ($exception instanceof NotFoundHttpException) {
        //         return response()->json([
        //             'message' => 'API route not found.'
        //         ], 404);
        //     }

        //     if ($exception instanceof MethodNotAllowedHttpException) {
        //         return response()->json([
        //             'message' => 'Method not allowed.'
        //         ], 405);
        //     }
        // }

        // // Default render dari Laravel
        // return parent::render($request, $exception);
    }
}
