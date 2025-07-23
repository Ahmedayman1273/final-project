<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\API\NewsController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\FaqController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AdminUserController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\StudentRequestController;
use App\Http\Controllers\API\RequestController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\DashboardController;
// Get authenticated user info
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Password Reset
Route::prefix('password')->middleware('throttle:5,1')->group(function () {
    Route::post('/send-code', [PasswordResetController::class, 'sendCode']);
    Route::post('/verify-code', [PasswordResetController::class, 'verifyCode']);
    Route::post('/reset', [PasswordResetController::class, 'resetPassword']);
});

// News
Route::middleware('auth:sanctum')->prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index']);
    Route::get('/{id}', [NewsController::class, 'show']);
    Route::post('/', [NewsController::class, 'store']);
    Route::put('/{news}', [NewsController::class, 'update']);
    Route::delete('/{news}', [NewsController::class, 'destroy']);
});


// Events
Route::middleware('auth:sanctum')->prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::get('/{id}', [EventController::class, 'show']);
    Route::post('/', [EventController::class, 'store']);
    Route::put('/{event}', [EventController::class, 'update']);
    Route::delete('/{event}', [EventController::class, 'destroy']);
});


   // FAQs (Admin)
   Route::middleware('auth:sanctum')->prefix('faqs')->group(function (){
        Route::get('/', [FaqController::class, 'index']);
        Route::post('/', [FaqController::class, 'store']);
        Route::put('/{id}', [FaqController::class, 'update']);
        Route::delete('/{id}', [FaqController::class, 'destroy']);
        });

    // Chatbot (For students/graduates)
    Route::prefix('chatbot')->group(function () {
        Route::get('/questions', [FaqController::class, 'questionsOnly']);
        Route::get('/answer/{id}', [FaqController::class, 'getAnswer']);
    });
// User profile image
Route::middleware('auth:sanctum')->put('/user/update-profile-image', [UserController::class, 'updateProfileImage']);

// Profile
Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'profile']);
    Route::post('/photo', [ProfileController::class, 'uploadPhoto']);
    Route::delete('/photo', [ProfileController::class, 'deletePhoto']);
});


// Student Requests
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/student-requests', [StudentRequestController::class, 'index']);
    Route::post('/student-requests', [StudentRequestController::class, 'store']);
    Route::delete('/student-requests/{id}', [StudentRequestController::class, 'destroy']);
});

// Request Types
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::put('/requests/{id}', [RequestController::class, 'update']);
    Route::delete('/requests/{id}', [RequestController::class, 'destroy']);
});

// Admin
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // Users
    Route::post('/create-user', [AdminUserController::class, 'createUser']);
    Route::post('/create-admin', [AdminUserController::class, 'createAdmin']);
    Route::patch('/change-user-type/{id}', [AdminUserController::class, 'changeUserType']);
    Route::post('/import-users', [AdminUserController::class, 'importUsersFromExcel']);
    // Student Requests (by status)
    Route::get('/student-requests/pending/{id}', [AdminUserController::class, 'showPendingRequestById']);
    Route::get('/student-requests/pending', [AdminUserController::class, 'getPendingRequests']);
    Route::get('/student-requests/accepted', [AdminUserController::class, 'getAcceptedRequests']);
    Route::get('/student-requests/rejected', [AdminUserController::class, 'getRejectedRequests']);
    // Update request status
    Route::patch('/student-requests/{id}/accept', [AdminUserController::class, 'acceptStudentRequest']);
    Route::patch('/student-requests/{id}/reject', [AdminUserController::class, 'rejectStudentRequest']);
    // Request Types
    Route::get('/request-types', [AdminUserController::class, 'getAllRequestTypes']);
    Route::get('/request-types/{id}', [AdminUserController::class, 'getRequestTypeById']);
    Route::post('/request-types', [AdminUserController::class, 'createRequestType']);
    Route::put('/request-types/{id}', [AdminUserController::class, 'updateRequestType']);
    Route::delete('/request-types/{id}', [AdminUserController::class, 'deleteRequestType']);
    // admin dashboard
    Route::middleware('auth:sanctum')->get('/dashboard', [DashboardController::class, 'index']);

});


// Notifications
Route::middleware('auth:api')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
});
// routes/api.php


