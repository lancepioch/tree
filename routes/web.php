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

Route::post('/user/update', 'ProfileController@update');

Route::resource('projects', 'ProjectController');

Route::get('/login/github', 'Auth\LoginController@redirectToProvider')->name('login');
Route::get('/login/github/callback', 'Auth\LoginController@handleProviderCallback');
Route::post('/logout', 'Auth\LoginController@logout')->name('logout');
