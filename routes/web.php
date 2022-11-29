<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PauseProjectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

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

Route::view('/', 'welcome')->name('welcome');


Route::middleware(['auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::post('/user/update', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/projects/{project}/pause', PauseProjectController::class)->name('projects.pause');
    Route::resource('projects', ProjectController::class);
});

Route::get('/login/github', [LoginController::class, 'redirectToProvider'])->name('login.github');
Route::get('/login/github/callback', [LoginController::class, 'handleProviderCallback']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
