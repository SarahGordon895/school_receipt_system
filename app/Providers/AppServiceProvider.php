<?php

namespace App\Providers;

use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        AbstractPaginator::useBootstrapFive();

        Route::bind('student', function (string $value) {
            $student = Student::findOrFail($value);
            $user = Auth::user();

            if ($user && $user->isParent() && ! $student->belongsToParent($user)) {
                abort(403, 'You can only access records for your own child.');
            }

            return $student;
        });

        Route::bind('log', function (string $value) {
            $log = NotificationLog::findOrFail($value);
            $user = Auth::user();

            if ($user && $user->isParent() && ! $user->parentStudents()->whereKey($log->student_id)->exists()) {
                abort(403, 'You can only access notifications for your own child.');
            }

            return $log;
        });

        View::composer(['layouts.app', 'layouts.guest'], function ($view) {
            $parentUnreadNotifications = 0;
            $appSetting = null;

            try {
                if (Auth::check() && Auth::user()->isParent()) {
                    $studentIds = Auth::user()->parentStudents()->pluck('id');
                    if ($studentIds->isNotEmpty()) {
                        $parentUnreadNotifications = NotificationLog::query()
                            ->whereIn('student_id', $studentIds)
                            ->whereNull('read_at')
                            ->count();
                    }
                }

                $appSetting = Setting::current();
            } catch (\Throwable) {
                // Database unavailable — views still render; connection errors surface on real queries.
            }

            $view->with('appSetting', $appSetting);
            $view->with('parentUnreadNotifications', $parentUnreadNotifications);
        });
    }
}
