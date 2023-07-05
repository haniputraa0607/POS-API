<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;
use League\OAuth2\Server\Exception\OAuthServerException;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    protected $dontReport = [
        OAuthServerException::class,
    ];

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
        $this->renderable(function (Throwable $e, $request) {
            if ($request->wantsJson() || $request->is('*api*')) {

            /**
             * @var \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\Response $e
             */
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                switch (true) {
                    case $e instanceof ValidationException:
                        $error = $this->invalidValidation($e->getMessage(), collect($e->errors())->flatten());
                        // $error = $this->invalidValidation($e->getMessage(), $e->errors());
                        break;
                    case $e instanceof NotFoundHttpException:
                    case $e instanceof RouteNotFoundException:
                        $error = $this->notFound($e->getMessage());
                        break;
                    default:
                        $error = $this->error($e->getMessage(), $statusCode);
                        break;
                }
            }
            return $error;
        });
    }
}
