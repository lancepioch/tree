<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::get('/home', [HomeController::class, 'index']);

Route::post('/user/update', [ProfileController::class, 'update']);

Route::post('/projects/{project}/pause', 'PauseProjectController');
Route::resource('projects', 'ProjectController');

Route::get('/login/github', [LoginController::class, 'redirectToProvider'])->name('login');
Route::get('/login/github/callback', [LoginController::class, 'handleProviderCallback']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
