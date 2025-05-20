<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GoogleSheetController;
use App\Http\Controllers\LeadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/login', [AuthController::class, 'login']);

// wrap these routes in a middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads/fetch', [LeadController::class, 'fetchFromApi']);
    Route::get('/leads/test-api', [LeadController::class, 'testApiConnection']);
});

Route::get('/clients', [LeadController::class, 'getAllClients']);

//AIzaSyBGl7EzDIxEdHcfdPivhv2PJ2wrV1RQgUM
Route::get('/fetch-google-sheet', [GoogleSheetController::class, 'fetchData']);
Route::post('/google-sheets-webhook', [GoogleSheetController::class, 'handleWebhook']);
Route::get('/create-watch', [GoogleSheetController::class, 'createWatchRequest']);

// For sending emails to dataroma
Route::post('/send-email', [EmailController::class, 'sendEmail']);
