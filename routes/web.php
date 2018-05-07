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

Route::get('/', 'HomeController@welcome');
Route::get('/home', 'HomeController@index');

Auth::routes();
Route::redirect('/login', '/login/github', 302);

Route::post('/user/update', 'ProfileController@update');

Route::resource('projects', 'ProjectController');

Route::get('/login/github', 'Auth\LoginController@redirectToProvider');
Route::get('/login/github/callback', 'Auth\LoginController@handleProviderCallback');
