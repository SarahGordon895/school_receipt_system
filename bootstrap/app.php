<?php

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $redirectMissingNotificationLog = function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Reminder log not found.'], 404);
            }

            if ($request->user()?->isParent() && Route::has('parent.notifications')) {
                return redirect()
                    ->route('parent.notifications')
                    ->with('warning', 'That notification is no longer available.');
            }

            return redirect()
                ->route('notification-logs.index')
                ->with('warning', 'That reminder log is no longer available. Use the list below to open a current record.');
        };

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($redirectMissingNotificationLog) {
            if ($e->getModel() !== NotificationLog::class) {
                return null;
            }

            return $redirectMissingNotificationLog($request);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($redirectMissingNotificationLog) {
            $previous = $e->getPrevious();

            if (! $previous instanceof ModelNotFoundException || $previous->getModel() !== NotificationLog::class) {
                return null;
            }

            return $redirectMissingNotificationLog($request);
        });
    })->create();
