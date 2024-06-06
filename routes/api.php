<?php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\UserController;

Route::post('register', 'App\Http\Controllers\Auth\RegisterController@register');
Route::post('login', 'App\Http\Controllers\Auth\LoginController@login');
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
Route::post('/verify', 'App\Http\Controllers\Auth\VerificationController@verify')->Middleware('auth:api');
Route::post('/reset-password', 'App\Http\Controllers\Auth\ResetPasswordController@reset')->middleware('auth:api');
Route::post('/forget-password', 'App\Http\Controllers\Auth\ForgotPasswordController@forgot');
Route::post('/forget-reset-password', 'App\Http\Controllers\Auth\ForgotPasswordController@reset');

// update profile
Route::put('/users/{id}', 'App\Http\Controllers\UserController@update')->middleware('auth:api');

// Route::put('/users/{id}/update-name', 'App\Http\Controllers\UserController@updateName');

