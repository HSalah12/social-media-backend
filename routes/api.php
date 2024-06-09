<?php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserProfileController;

// register
Route::post('register', 'App\Http\Controllers\Auth\RegisterController@register');
// login
Route::post('login', 'App\Http\Controllers\Auth\LoginController@login');
// logout
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
// verify
Route::post('/verify', 'App\Http\Controllers\Auth\VerificationController@verify')->Middleware('auth:api');
// reset password
Route::post('/reset-password', 'App\Http\Controllers\Auth\ResetPasswordController@reset')->middleware('auth:api');
// forget password
Route::post('/forget-password', 'App\Http\Controllers\Auth\ForgotPasswordController@forgot');
// forget reset password
Route::post('/forget-reset-password', 'App\Http\Controllers\Auth\ForgotPasswordController@reset');
// update profile
Route::post('/profile', [UserProfileController::class, 'store']);
// update profile
Route::put('/users/{id}', 'App\Http\Controllers\UserController@update')->middleware('auth:api');
// Return profile Data
Route::get('/profile/{id}', 'App\Http\Controllers\UserProfileController@show')->middleware('auth:api');;
// Delete Account
Route::delete('/profile/{id}', 'App\Http\Controllers\UserProfileController@destroy')->middleware('auth:api');
// Show account data

Route::get('/profile/{id}', 'App\Http\Controllers\UserProfileController@showdata')->middleware('auth:api');


// follow and unfollow

Route::post('/follow/{user}', 'App\Http\Controllers\UserProfileController@follow');
Route::post('/unfollow/{user}', 'App\Http\Controllers\UserProfileController@unfollow');

// GET activities

Route::get('profile/{id}/activities', 'App\Http\Controllers\UserProfileController@activities')->middleware('auth:api');


// deactivate & delete profile

Route::post('/profile/deactivate', 'App\Http\Controllers\UserProfileController@deactivate')->middleware('auth:api');
