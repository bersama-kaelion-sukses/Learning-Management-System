<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\CourseController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// routes/web.php
Route::get('/api/item/{item}/essay', [CourseController::class, 'getEssay']);
Route::get('/api/item/{item}/forum', [CourseController::class, 'getForum']);
Route::get('/api/item/{item}/quiz', [CourseController::class, 'getQuiz']);

