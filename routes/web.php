<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LogoutController;

Route::get('/', function () {
    return view('welcome');
});

Route::view('register', 'auth.register');
Route::view('login', 'auth.login');
Route::post('register', 'App\Http\Controllers\Auth\RegisterController@register')->name('register');
Route::post('login', 'App\Http\Controllers\Auth\LoginController@login')->name('login');
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

