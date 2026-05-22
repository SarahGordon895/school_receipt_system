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

        View::composer('*', function ($view) {
            $parentUnreadNotifications = 0;

            if (Auth::check() && Auth::user()->isParent()) {
                $studentIds = Auth::user()->parentStudents()->pluck('id');
                if ($studentIds->isNotEmpty()) {
                    $parentUnreadNotifications = NotificationLog::query()
                        ->whereIn('student_id', $studentIds)
                        ->whereNull('read_at')
                        ->count();
                }
            }

            $view->with('appSetting', Setting::first());
            $view->with('parentUnreadNotifications', $parentUnreadNotifications);
        });
    }
}
