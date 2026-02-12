<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BatchVerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocumentBatchController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Public verification (no auth required) - rate limited to prevent scraping
Route::middleware(['throttle:verification'])
    ->get('/verify/{token}', [VerificationController::class, 'show'])
    ->name('verify');

// Public document access via verification token (for viewing after QR scan)
Route::middleware(['throttle:verification'])->group(function () {
    Route::get('/verify/{token}/preview', [VerificationController::class, 'preview'])->name('verify.preview');
    Route::get('/verify/{token}/download', [VerificationController::class, 'download'])->name('verify.download');
});

// Public batch verification (no auth required)
Route::middleware(['throttle:verification'])->prefix('verify-batch')->group(function () {
    Route::get('/{token}', [BatchVerificationController::class, 'show'])->name('verify.batch');
    Route::get('/{token}/search', [BatchVerificationController::class, 'search'])->name('verify.batch.search');
    Route::get('/{token}/{document}/preview', [BatchVerificationController::class, 'preview'])->name('verify.batch.preview');
    Route::get('/{token}/{document}/download', [BatchVerificationController::class, 'download'])->name('verify.batch.download');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (with global web throttle)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'throttle:web'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    /*
    |----------------------------------------------------------------------
    | Notification Routes
    |----------------------------------------------------------------------
    */
    Route::get('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    
    /*
    |----------------------------------------------------------------------
    | Document Routes (Admin & Operator) - with upload throttle
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:admin,operator'])->group(function () {
        Route::resource('documents', DocumentController::class)->except(['store', 'show', 'destroy']);
        
        // Upload with stricter throttle
        Route::middleware(['throttle:uploads'])
            ->post('/documents', [DocumentController::class, 'store'])
            ->name('documents.store');
            
        Route::post('/documents/{document}/finalize', [DocumentController::class, 'finalize'])->name('documents.finalize');

        // Batch management
        Route::resource('batches', DocumentBatchController::class)->except(['edit', 'update']);
        Route::get('batches/{batch}/qr', [DocumentBatchController::class, 'downloadQr'])->name('batches.qr');
        Route::get('batches/{batch}/preview-first', [DocumentBatchController::class, 'previewFirstDocument'])->name('batches.previewFirst');
        Route::post('batches/{batch}/apply-qr', [DocumentBatchController::class, 'applyQr'])->name('batches.applyQr');
    });

    /*
    |----------------------------------------------------------------------
    | Document Preview & Download (All authenticated users)
    |----------------------------------------------------------------------
    */
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::post('/documents/{document}/revoke', [DocumentController::class, 'revoke'])->name('documents.revoke');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    /*
    |----------------------------------------------------------------------
    | Signature Routes (Signer) - with signature throttle
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:admin,signer', 'throttle:signatures'])->prefix('signing')->name('signatures.')->group(function () {
        Route::get('/pending', [SignatureController::class, 'pending'])->name('pending');
        Route::get('/history', [SignatureController::class, 'history'])->name('history');
        Route::get('/{document}', [SignatureController::class, 'show'])->name('show');
        Route::post('/{document}/approve', [SignatureController::class, 'approve'])->name('approve');
        Route::post('/{document}/reject', [SignatureController::class, 'reject'])->name('reject');
    });
    
    /*
    |----------------------------------------------------------------------
    | Signer Self-Upload Routes - with upload throttle
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:signer'])->prefix('my-documents')->name('signer-documents.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SignerDocumentController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\SignerDocumentController::class, 'create'])->name('create');
        
        Route::middleware(['throttle:uploads'])
            ->post('/', [\App\Http\Controllers\SignerDocumentController::class, 'store'])
            ->name('store');
    });
    
    /*
    |----------------------------------------------------------------------
    | Admin Only Routes
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        
        // Department management - super admin only (controller handles auth)
        Route::resource('departments', DepartmentController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
