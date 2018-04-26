<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Updates the current user.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        auth()->user()->fill($request->all())->save();

        return redirect()->action('HomeController@index');
    }
}
