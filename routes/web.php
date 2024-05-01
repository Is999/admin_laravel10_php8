<?php

use App\Http\Controllers\GoogleSecretController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    \Illuminate\Support\Facades\View::addExtension('html', 'php');
    return \Illuminate\Support\Facades\View::file(public_path() . '/index.html');

});

// 创建谷歌验证码
Route::match(['GET', 'POST'], '/mfa/secret/{sign}', [GoogleSecretController::class, 'secret'])->name('user.secret');
Route::post('/mfa/buildSecret', [GoogleSecretController::class, 'buildSecretKey'])->name('user.buildSecret');

