<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminIncidentController;
use App\Http\Controllers\Api\AdminCategoryController;
use App\Http\Controllers\Api\AdminLevelController;
use App\Http\Controllers\Api\AdminLogController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ImageAnalysisController;

// Public Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'loginAdmin']);
Route::post('/login-unified', [AuthController::class, 'loginUnified']);
Route::post('/register', [AuthController::class, 'register']);

// Password Reset (OTP-based)
Route::post('/forgot-password',    [ForgotPasswordController::class, 'sendOtp']);
Route::post('/reset-password',     [ForgotPasswordController::class, 'resetPassword']);

// Email Verification
Route::post('/send-verify-email',  [ForgotPasswordController::class, 'sendVerifyEmail']);
Route::post('/verify-email',       [ForgotPasswordController::class, 'verifyEmail']);

// Public incident routes (no auth required)
Route::get('/public/map', [PublicController::class, 'map']);
Route::get('/public/su-co', [PublicController::class, 'search']);
Route::get('/public/su-co/{id}', [PublicController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin API
    Route::prefix('admin')->group(function () {
        // Users
        Route::get('/users/search', [AdminUserController::class, 'search']);
        Route::patch('/users/{id}/password', [AdminUserController::class, 'changePassword']);
        Route::patch('/users/{id}/lock', [AdminUserController::class, 'lock']);
        Route::patch('/users/{id}/unlock', [AdminUserController::class, 'unlock']);
        Route::apiResource('users', AdminUserController::class);

        // Incidents
        Route::get('/su-co/search', [AdminIncidentController::class, 'search']);
        Route::patch('/su-co/{id}/status', [AdminIncidentController::class, 'updateStatus']);
        Route::apiResource('su-co', AdminIncidentController::class);

        // Categories & Levels
        Route::apiResource('loai-su-co', AdminCategoryController::class);
        Route::apiResource('muc-do', AdminLevelController::class);

        // System Logs
        Route::get('/logs', [AdminLogController::class, 'index']);

        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // Notifications
        Route::get('/notifications', [AdminNotificationController::class, 'index']);
        Route::post('/notifications', [AdminNotificationController::class, 'store']);
    });

    // User Profile API
    Route::get('/me', [UserController::class, 'me']);
    Route::put('/me', [UserController::class, 'updateProfile']);
    Route::patch('/me/password', [UserController::class, 'changePassword']);

    // User Incidents API
    Route::post('/su-co', [UserController::class, 'storeIncident']);
    Route::get('/my-su-co', [UserController::class, 'myIncidents']);
    Route::get('/su-co/map', [MapController::class, 'index']);
    Route::patch('/su-co/{id}/tiep-nhan', [UserController::class, 'tiepNhan']);
    Route::get('/su-co/{id}', [UserController::class, 'showIncident']);
    Route::patch('/su-co/{id}', [UserController::class, 'updateIncident']);

    // User Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);

    // AI API
    Route::post('/ai/phan-tich/{id}', [AiController::class, 'analyze']);
    Route::post('/ai/check-duplicate/{id}', [AiController::class, 'checkDuplicate']);
    Route::post('/ai/check-duplicate-pre', [AiController::class, 'checkSpamAndDuplicatePre']);

    // Image Analysis (Groq Vision)
    Route::post('/analyze-image', [ImageAnalysisController::class, 'analyze']);
});
