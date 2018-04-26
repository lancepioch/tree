<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectController extends Controller
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
     * Creates a new project.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        Project::create($request->all());

        return redirect()->action('HomeController@index');
    }
}
