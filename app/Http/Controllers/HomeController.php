<?php

namespace App\Http\Controllers;

use Exception;
use Github\Client;
use Github\ResultPager;
use Illuminate\Http\Request;
use Themsaid\Forge\Forge;

class HomeController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $forge = new Forge(auth()->user()->forge_token);
            $servers = $forge->servers();
        } catch (Exception $exception) {
            $servers = [];
            $forgeException = 'Your Forge API Token is invalid.';
        }

        try {
            $github = new Client();
            $github->authenticate(auth()->user()->github_token, null, Client::AUTH_HTTP_PASSWORD);

            $paginator = new ResultPager($github);
            $repositories = $paginator->fetchAll($github->api('me'), 'repositories', ['all']);
        } catch (Exception $exception) {
            $repositories = [];
            $githubException = 'Your Github API Token is invalid.';
        }

        return view('home')->with(compact('servers', 'repositories', 'forgeException', 'githubException'));
    }
}
