<?php

use App\Http\Controllers\GoogleSecretController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

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
    View::addExtension('html', 'php');
    return View::file(public_path() . '/index.html');

});

// 创建谷歌验证码
Route::match(['GET', 'POST'], '/mfa/secret/{sign}', [GoogleSecretController::class, 'secret'])->name('user.secret');
Route::post('/mfa/buildSecret', [GoogleSecretController::class, 'buildSecretKey'])->name('user.buildSecret');

Route::get('/uploads/{file}', function (Request $request, string $file) {
    $filePath = 'uploads/' . $file;
    if (Storage::exists($filePath)) {
        return response()->file(storage_path('app/' . $filePath));
    } else {
        abort(404);
    }
});