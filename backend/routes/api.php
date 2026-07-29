<?php

use App\Http\Controllers\ExcelTemplateController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/template', [ExcelTemplateController::class, 'show']);
Route::post('/template/upload', [ExcelTemplateController::class, 'upload']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders', [OrderController::class, 'index']);
