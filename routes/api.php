<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']); // Legacy
Route::post('/register/user', [AuthController::class, 'registerUser']);
Route::post('/register/advertiser', [AuthController::class, 'registerAdvertiser']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forget']);
Route::post('/reset-password', [AuthController::class, 'reset']);

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