<?php

use App\Http\Controllers\Api\GuestCheckoutController;
use App\Http\Controllers\Api\ManualPaymentController;
use App\Http\Controllers\Api\PublicEventController;
use App\Http\Controllers\Api\PublicOrderStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/events', [PublicEventController::class, 'index']);
Route::get('/events/{event:slug}', [PublicEventController::class, 'show']);
Route::post('/orders', [GuestCheckoutController::class, 'store'])->middleware('throttle:10,1');
Route::post('/orders/{order}/payments/manual', [ManualPaymentController::class, 'store'])->middleware('throttle:10,1');
Route::post('/orders/status', PublicOrderStatusController::class)->middleware('throttle:10,1');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
