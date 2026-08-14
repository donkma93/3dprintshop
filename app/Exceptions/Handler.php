<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // API: trả JSON gọn thay vì raw "No query results for model..."
        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $model = class_basename($e->getModel() ?: 'Record');
            $ids = implode(', ', array_map('strval', $e->getIds()));

            return response()->json([
                'success' => false,
                'message' => $ids !== ''
                    ? "Không tìm thấy {$model} #{$ids}."
                    : "Không tìm thấy {$model}.",
            ], 404);
        });

        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $prev = $e->getPrevious();
            if ($prev instanceof ModelNotFoundException) {
                $model = class_basename($prev->getModel() ?: 'Record');
                $ids = implode(', ', array_map('strval', $prev->getIds()));

                return response()->json([
                    'success' => false,
                    'message' => $ids !== ''
                        ? "Không tìm thấy {$model} #{$ids}."
                        : "Không tìm thấy {$model}.",
                ], 404);
            }

            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tài nguyên.',
            ], 404);
        });
    }
}
