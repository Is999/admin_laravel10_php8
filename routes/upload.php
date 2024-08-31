<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// 须验登录Token
// except.不验证权限的接口(只验证token, 该路由无须加入权限表)
// 上传文件
Route::middleware(['adminAuth', 'throttle:30,1'])->prefix('upload')->name('except.upload.')->group(function () {
    Route::post('image', [UploadController::class, 'image'])->name('image'); // 上传图片 限制图片格式
    Route::post('images', [UploadController::class, 'images'])->name('images'); // 批量上传图片 限制图片格式
    Route::post('file', [UploadController::class, 'file'])->name('file'); // 上传文件
    Route::post('files', [UploadController::class, 'files'])->name('files'); // 批量上传文件
});




