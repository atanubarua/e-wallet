<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MoneyTransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::post('customer/register', [AuthController::class, 'register']);
Route::post('customer/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('send-money', [MoneyTransferController::class, 'send']);
});
