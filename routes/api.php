<?php

use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\RevenueController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/instructors', [InstructorController::class, 'index']);
Route::get('/instructors/{instructor}', [InstructorController::class, 'show']);
Route::get('/instructors/{instructor}/payouts', [InstructorController::class, 'payouts']);

Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show']);
Route::post('/subscriptions', [SubscriptionController::class, 'store']);
Route::post('/subscriptions/{subscription}/refund', [SubscriptionController::class, 'refund']);

Route::post('/revenue/accrue', [RevenueController::class, 'accrue']);

Route::get('/payouts', [PayoutController::class, 'index']);
Route::get('/payouts/{payout}', [PayoutController::class, 'show']);
Route::post('/payouts/process', [PayoutController::class, 'process']);
