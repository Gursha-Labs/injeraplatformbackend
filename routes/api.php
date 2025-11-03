<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forget'])->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:10,1');
Route::post('/uploadFile', [App\Http\Controllers\FileUploadController::class, 'uploadFile']);

// Public Routes
Route::get('/advertisers/{userId}/profile', [ProfileController::class, 'getAdvertiserPublicProfile']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Profile Management
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile/user', [ProfileController::class, 'updateUserProfile']);
    Route::put('/profile/advertiser', [ProfileController::class, 'updateAdvertiserProfile']);
    Route::delete('/profile/picture', [ProfileController::class, 'deleteProfilePicture']);
});