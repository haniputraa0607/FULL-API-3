<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof \Laravel\Passport\Exceptions\MissingScopeException) {
            return response()->json(['error' => 'Unauthenticated'], 403);
        } elseif ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        } elseif ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException && !app()->environment('production')) {
            $result = app('App\Http\Controllers\MockAPI')->mock($request);
            if ($result) {
                return response($result);
            }
            return response([
                'status' => 'fail',
                'messages' => ['resource not found']
            ], 404);
        } elseif ($exception instanceof \Illuminate\Validation\ValidationException) {
            return response([
                'status' => 'fail',
                'messages' => array_column($exception->errors(), 0),
            ]);
        }
        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'fail',
                'messages' => config('app.env') == 'production' ? 'Terjadi kesalahan saat memproses permintaan' : [$exception->getMessage()]
            ]);
        }
        return parent::render($request, $exception);
    }

    protected function convertExceptionToArray(Exception $e)
    {
        return config('app.debug') ? [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->map(function ($trace) {
                return Arr::except($trace, ['args']);
            })->all(),
        ] : [
            'status'  => 'fail',
            'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error',
        ];
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json(['error' => 'Unauthenticated.'], 401);
    }
}
