<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CategoryController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/ping', function(){
    return['pong' => true];
});

//rota login

Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');

Route::post('/auth/login', [AuthController::class, 'login']); //feito
Route::post('/auth/logout', [AuthController::class, 'logout']);//feito
Route::post('/auth/refresh', [AuthController::class, 'refresh']);//feito

Route::get('/user', [UserController::class, 'read']); //info user //feito
Route::put('/user', [UserController::class, 'update']); //feito
Route::post('/user/avatar', [UserController::class, 'updateAvatar']); //feito

Route::post('/user', [AuthController::class, 'create']); //feito

//favortios
Route::get('/user/favorites', [UserController::class, 'favorites']); //feito
Route::post('/user/favorite', [UserController::class, 'toggleFavorites']);

//categories
Route::post('/categories', [CategoryController::class, 'create']); //feito
Route::get('/categories', [CategoryController::class, 'all']); //feito
Route::get('/categorie/{id}', [CategoryController::class, 'read']); //feito


Route::post('/posts', [PostController::class, 'create']); //feito
Route::get('/posts', [PostController::class, 'list']); //feito
Route::get('/post/{id}', [PostController::class, 'single']); //feito
Route::delete('/post/{id}', [PostController::class, 'delete']); //feito
Route::put('/post/{id}', [PostController::class, 'update']); //feito

Route::post('post/images/{id}', [PostController::class, 'updateImages']); //feito
Route::delete('post/images/{id}', [PostController::class, 'deleteImage']); //feito

Route::get('/myposts', [PostController::class, 'myPosts']); //feito



//busca
Route::get('/search', [PostController::class, 'search']); //feito