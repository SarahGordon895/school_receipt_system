<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\NotificationLogController;
use App\Http\Controllers\PaymentCategoryController;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->home_route);
    }

    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('role:super_admin,school_admin')
        ->name('dashboard');

    Route::get('/parent/dashboard', ParentDashboardController::class)
        ->middleware('role:parent')
        ->name('parent.dashboard');
    Route::get('/parent/students/{student}', [ParentDashboardController::class, 'showStudent'])
        ->middleware('role:parent')
        ->name('parent.students.show');
    Route::get('/parent/notifications', [ParentDashboardController::class, 'notifications'])
        ->middleware('role:parent')
        ->name('parent.notifications');
    Route::post('/parent/notifications/{log}/read', [ParentDashboardController::class, 'markNotificationRead'])
        ->middleware('role:parent')
        ->name('parent.notifications.read');
    Route::post('/parent/notifications/read-all', [ParentDashboardController::class, 'markAllNotificationsRead'])
        ->middleware('role:parent')
        ->name('parent.notifications.read-all');

    Route::middleware('role:super_admin,school_admin')->group(function () {
        Route::resource('receipts', ReceiptController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/receipts/partial', [ReceiptController::class, 'partial'])->name('receipts.partial'); // AJAX table
        Route::get('/receipts/{receipt}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');   // Download PDF

        Route::resource('students', StudentController::class)->except(['show']);
        Route::get('/students/import', [StudentController::class, 'importForm'])->name('students.import.form');
        Route::post('/students/import', [StudentController::class, 'importStore'])->name('students.import.store');
        Route::get('/api/students', [StudentController::class, 'search'])->name('api.students.search');
        Route::resource('payment-categories', PaymentCategoryController::class)->except(['show']);
        Route::resource('fee-structures', FeeStructureController::class)->except(['show']);

    // Reports routes
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/unpaid', [ReportController::class, 'unpaid'])->name('reports.unpaid');
        Route::get('/notification-logs', [NotificationLogController::class, 'index'])->name('notification-logs.index');
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::post('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
        Route::post('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    });

});

require __DIR__.'/auth.php';
