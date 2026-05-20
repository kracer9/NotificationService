<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\NotificationController;

Route::controller(SubscriberController::class)->group(function () {
    Route::post('/subscribers', 'store');
    Route::get('/subscribers', 'list');
});

Route::controller(NotificationController::class)->group(function () {
    Route::post('/notifications', 'publish');
    Route::get('/notifications', 'list');
    Route::get('/notifications/by_subscriber/{subscriberId}', 'listBySubscriber');
});
