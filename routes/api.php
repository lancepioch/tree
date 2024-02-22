<?php

use App\Http\Controllers\Webhooks\GithubPullRequestController;
use Illuminate\Support\Facades\Route;

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

Route::post('/webhooks/github/pullrequest', GithubPullRequestController::class)->name('webhooks.github.pullrequest');
