<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::post('customer/register', [AuthController::class, 'register']);
Route::post('customer/login', [AuthController::class, 'login']);
