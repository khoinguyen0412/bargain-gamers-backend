<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

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



Route::middleware('auth:sanctum')->group(function(){
    Route::patch('/user/{username}/edit',[UserController::class,'edit']);
    Route::post('/logout',[AuthController::class,'logout']);
});

Route::get('/checkToken',[AuthController::class,'validateToken']);
Route::post('/register', [AuthController::class,'register']);
Route::get('/unauthorized',[AuthController::class,'unauthorized'])->name('unauthorized');
Route::post('/login',[AuthController::class,'login'])->name('login');
Route::get('/user/{username}',[UserController::class,'view']);





