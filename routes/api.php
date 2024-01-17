<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Authentication System API...............................
Route::controller(AuthController::class)->group(function()
{
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/otp-login', 'otpLogin');
    Route::post('/verify-otp', 'verifyOTP');

});

Route::middleware('auth:sanctum')->group( function ()
{
    //Logout System.......................................
    Route::post('/logout', [AuthController::class, 'logout']);

    //Profile Info System.................................
    Route::get('/profile-info', [UserController::class, 'get_profile_info']);
    Route::patch('/profile-info', [UserController::class, 'profile_info']);
    Route::get('/profile-suggest', [UserController::class, 'profile_suggest']);
});
