<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\AdFeedController;
use App\Http\Controllers\Api\AdViewController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\AdvertiserProfileController;
use App\Http\Controllers\Api\DashboardController;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RecentSearchController;

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:10,1');
Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forget'])->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:10,1');


// Protected Routes
Route::middleware(['auth:sanctum','blocked'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    // User Profile
    Route::get('/profile/user', [UserProfileController::class, 'show']);
    Route::post('/profile/user', [UserProfileController::class, 'update']);
    Route::delete('/profile/user/picture', [UserProfileController::class, 'deleteProfilePicture']);

    // Advertiser Profile
    Route::get('/profile/advertiser', [AdvertiserProfileController::class, 'show']);
    Route::post('/profile/advertiser', [AdvertiserProfileController::class, 'update']);
    Route::get('/advertiser/public/{userId}', [AdvertiserProfileController::class, 'publicProfile']);
    Route::delete('/profile/advertiser/picture', [AdvertiserProfileController::class, 'deleteProfilePicture']);
    Route::get('/owen-videos', [AdvertiserProfileController::class, 'owen_videos']);
    Route::get('/advertiser/video/{id}', [AdvertiserProfileController::class, 'get_video_by_id']);

    // Unified Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/block-user/{userId}', [AdminController::class, 'block_user']);
    Route::get('/unblock-user/{userId}', [AdminController::class, 'unblock_user']);

    // Ad uploads
    Route::post('/ads/upload', [AdController::class, 'upload']);

    // User comments
    Route::post('/ads/{ad}/comment', [CommentController::class, 'comment']);

    // Advertiser replies
    Route::post('/ads/{ad}/comments/{comment}/reply', [CommentController::class, 'reply']);
    // Search videos
    Route::get("/seach-video/{search_term}",[AdController::class,"search_ads"]);
    Route::get('/recent-searches', [RecentSearchController::class, 'recent_searches']);
    // Ad views
    Route::post('/ads/{ad}/view', [AdViewController::class, 'track']);
    Route::get('/user/points', [AdViewController::class, 'points']);

    // Orders
    Route::prefix('orders')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
    Route::get('/my-orders', [OrderController::class, 'my_orders']);
    Route::delete('/delete-all-orders', [OrderController::class, 'deleteAllOrdersForUser']);
    Route::delete('/orders/delete/{orderId}', [OrderController::class, 'delete_order_by_id']);
    });
});


Route::post('/uploadFile', [App\Http\Controllers\FileUploadController::class, 'uploadFile']);


// Public Routes
Route::get('/categories', [AdController::class, 'getCategories'])->name('api.categories');
// Public Routes for ads feed
Route::get('/ads/feed', [AdFeedController::class, 'index']);
Route::get('/ads/{ad}', [AdFeedController::class, 'show']);
Route::get('/ads/{ad}/comments', [CommentController::class, 'index']);
Route::get('/ads/{ad}/comments/{comment}/replies', [CommentController::class, 'replies']);