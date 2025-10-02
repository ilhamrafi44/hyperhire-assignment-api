<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PeopleController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/people', [PeopleController::class, 'index']);
Route::post('/people/{id}/like', [PeopleController::class, 'like']);
Route::post('/people/{id}/dislike', [PeopleController::class, 'dislike']);
Route::get('/people/liked', [PeopleController::class, 'liked']);
