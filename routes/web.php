<?php

use App\Http\Controllers\BankPaymentSubmissionController;
use App\Http\Controllers\ClearanceCertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationLogController;
use App\Http\Controllers\PaymentCategoryController;
use App\Http\Controllers\ParentBankPaymentController;
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
        ->middleware('role:school_admin,super_admin')
        ->name('dashboard');

    Route::get('/parent/dashboard', ParentDashboardController::class)
        ->middleware('role:parent')
        ->name('parent.dashboard');
    Route::get('/parent/students/{student}', [ParentDashboardController::class, 'showStudent'])
        ->middleware('role:parent')
        ->name('parent.students.show');
    Route::get('/parent/students/{student}/clearance-certificate', ClearanceCertificateController::class)
        ->middleware('role:parent')
        ->name('parent.students.clearance-certificate');
    Route::get('/parent/notifications', [ParentDashboardController::class, 'notifications'])
        ->middleware('role:parent')
        ->name('parent.notifications');
    Route::post('/parent/notifications/{log}/read', [ParentDashboardController::class, 'markNotificationRead'])
        ->middleware('role:parent')
        ->name('parent.notifications.read');
    Route::post('/parent/notifications/read-all', [ParentDashboardController::class, 'markAllNotificationsRead'])
        ->middleware('role:parent')
        ->name('parent.notifications.read-all');
    Route::get('/parent/bank-payments', [ParentBankPaymentController::class, 'index'])
        ->middleware('role:parent')
        ->name('parent.bank-payments.index');
    Route::post('/parent/bank-payments', [ParentBankPaymentController::class, 'store'])
        ->middleware('role:parent')
        ->name('parent.bank-payments.store');

    // School operations (bursar + super admin)
    Route::middleware('role:school_admin,super_admin')->group(function () {
        Route::resource('receipts', ReceiptController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::get('/receipts/partial', [ReceiptController::class, 'partial'])->name('receipts.partial');
        Route::get('/receipts/{receipt}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');

        Route::get('/students/import', [StudentController::class, 'importForm'])->name('students.import.form');
        Route::post('/students/import', [StudentController::class, 'importStore'])->name('students.import.store');
        Route::get('/students/import/result', [StudentController::class, 'importResult'])->name('students.import.result');
        Route::resource('students', StudentController::class)->except(['show']);
        Route::get('/students/{student}/clearance-certificate', ClearanceCertificateController::class)
            ->name('students.clearance-certificate');
        Route::get('/api/students', [StudentController::class, 'search'])->name('api.students.search');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/reports/fee-position', [ReportController::class, 'feePosition'])->name('reports.fee-position');
        Route::get('/reports/fee-position/pdf', [ReportController::class, 'feePositionPdf'])->name('reports.fee-position.pdf');
        Route::match(['get', 'post'], '/reports/receipts', [ReportController::class, 'receiptRegister'])->name('reports.receipts');
        Route::post('/reports/receipts/pdf', [ReportController::class, 'receiptRegisterPdf'])->name('reports.receipts.pdf');
        Route::get('/reports/unpaid', [ReportController::class, 'unpaid'])->name('reports.unpaid');
        Route::get('/reports/unpaid/pdf', [ReportController::class, 'unpaidPdf'])->name('reports.unpaid.pdf');
        Route::get('/reports/paid', [ReportController::class, 'paid'])->name('reports.paid');
        Route::get('/reports/clearance', [ReportController::class, 'clearance'])->name('reports.clearance');
        Route::get('/reports/clearance/pdf', [ReportController::class, 'clearancePdf'])->name('reports.clearance.pdf');
        Route::match(['get', 'post'], '/reports/messages', [ReportController::class, 'messageHistory'])->name('reports.messages');
        Route::post('/reports/messages/pdf', [ReportController::class, 'messageHistoryPdf'])->name('reports.messages.pdf');
        Route::match(['get', 'post'], '/reports/bank-proofs', [ReportController::class, 'bankProofs'])->name('reports.bank-proofs');
        Route::post('/reports/bank-proofs/pdf', [ReportController::class, 'bankProofsPdf'])->name('reports.bank-proofs.pdf');
        Route::post('/reports/unpaid/send-reminders', [ReportController::class, 'sendReminders'])->name('reports.unpaid.send-reminders');
        Route::post('/notification-logs/{notification_log}/resend', [NotificationLogController::class, 'resend'])->name('notification-logs.resend');
        Route::post('/notification-logs/{notification_log}/refresh-status', [NotificationLogController::class, 'refreshStatus'])->name('notification-logs.refresh-status');
        Route::post('/notification-logs/{notification_log}/mark-delivered', [NotificationLogController::class, 'markDelivered'])->name('notification-logs.mark-delivered');
        Route::get('/notification-logs/send', [NotificationLogController::class, 'sendCreate'])->name('notification-logs.send.create');
        Route::post('/notification-logs/send', [NotificationLogController::class, 'sendStore'])->name('notification-logs.send.store');
        Route::post('/students/{student}/send-reminder', [NotificationLogController::class, 'sendToStudent'])->name('students.send-reminder');
        Route::resource('notification-logs', NotificationLogController::class);
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::post('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
        Route::post('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

        Route::get('/bank-payments', [BankPaymentSubmissionController::class, 'index'])->name('bank-payments.index');
        Route::get('/bank-payments/{bankPayment}', [BankPaymentSubmissionController::class, 'show'])->name('bank-payments.show');
        Route::get('/bank-payments/{bankPayment}/download', [BankPaymentSubmissionController::class, 'download'])->name('bank-payments.download');
        Route::post('/bank-payments/{bankPayment}/approve', [BankPaymentSubmissionController::class, 'approve'])->name('bank-payments.approve');
        Route::post('/bank-payments/{bankPayment}/reject', [BankPaymentSubmissionController::class, 'reject'])->name('bank-payments.reject');
    });

    // Super admin (developer): system configuration only — not school daily operations
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('payment-categories', PaymentCategoryController::class)->except(['show']);
        Route::resource('fee-structures', FeeStructureController::class)->except(['show']);
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/auth.php';
