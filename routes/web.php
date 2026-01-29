<?php

use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentCategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\StudentController;
use App\Models\ClassRoom;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard'); });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('receipts', ReceiptController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::resource('classes', ClassRoomController::class)->except(['show']);
    Route::resource('streams', StreamController::class)->except(['show']);

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // AJAX endpoint to fetch streams for a class (for dependent dropdown)
    Route::get('/api/classes/{class}/streams', function (ClassRoom $class) {
        return $class->streams()->select('id', 'name')->orderBy('name')->get();
    })->name('api.class.streams');

    Route::get('/receipts/partial', [ReceiptController::class, 'partial'])->name('receipts.partial'); // AJAX table
    Route::get('/receipts/{receipt}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');   // Download PDF

    Route::resource('students', StudentController::class)->except(['show']);
    Route::get('/students/import', [StudentController::class, 'importForm'])->name('students.import.form');
    Route::post('/students/import', [StudentController::class, 'importStore'])->name('students.import.store');
    Route::get('/api/students', [StudentController::class, 'search'])->name('api.students.search');
    Route::resource('payment-categories', PaymentCategoryController::class)->except(['show']);

    // Reports routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::post('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::post('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/api/classes/{class}/streams', [ReportController::class, 'getStreamsByClass'])->name('api.reports.class.streams');

});

require __DIR__.'/auth.php';
