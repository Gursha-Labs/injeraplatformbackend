<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\AdFeedController;
use App\Http\Controllers\Api\AdViewController;
use App\Http\Controllers\Api\CommentController;

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:10,1');
Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forget'])->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:10,1');

// Public Routes
Route::get('/advertisers/{userId}/profile', [ProfileController::class, 'getAdvertiserPublicProfile']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    // Profile Management
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile/user', [ProfileController::class, 'updateUserProfile']);
    Route::put('/profile/advertiser', [ProfileController::class, 'updateAdvertiserProfile']);
    Route::delete('/profile/picture', [ProfileController::class, 'deleteProfilePicture']);
});


Route::post('/uploadFile', [App\Http\Controllers\FileUploadController::class, 'uploadFile']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ads/upload', [AdController::class, 'upload']);

    // User comments
    Route::post('/ads/{ad}/comment', [CommentController::class, 'comment']);
    // Advertiser replies
    Route::post('/ads/{ad}/comments/{comment}/reply', [CommentController::class, 'reply']);
    
    // Ad views
    Route::post('/ads/{ad}/view', [AdViewController::class, 'track']);
    Route::get('/user/points', [AdViewController::class, 'points']);
});
// Public Routes
Route::get('/categories', [AdController::class, 'getCategories'])->name('api.categories');
// Public Routes for ads feed
Route::get('/ads/feed', [AdFeedController::class, 'index']);
Route::get('/ads/{ad}', [AdFeedController::class, 'show']);
Route::get('/ads/{ad}/comments', [CommentController::class, 'index']);