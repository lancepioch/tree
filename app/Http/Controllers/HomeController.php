<?php

namespace App\Http\Controllers;

use Exception;
use Github\Client;
use Github\ResultPager;
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
        $this->middleware('auth')->except('welcome');
    }

    /**
     * Show the application dashboard.
     *
     * @param Forge $forge
     * @return \Illuminate\View\View
     */
    public function index(Forge $forge)
    {
        $githubException = null;
        $forgeException = null;
        $servers = [];
        $repositories = [];

        if (is_null(auth()->user()->forge_token)) {
            $forgeException = 'Please enter a valid Forge API Token from: Laravel Forge > My Account > API';

            return view('home')->with(compact('servers', 'repositories', 'forgeException', 'githubException'));
        }

        try {
            $forge = $forge->setApiKey(auth()->user()->forge_token, null);
            $servers = $forge->servers();
        } catch (Exception $exception) {
            $forgeException = 'Your Forge API Token is invalid.';
        }

        try {
            $github = new Client();
            $github->authenticate(auth()->user()->github_token, null, Client::AUTH_HTTP_PASSWORD);

            $paginator = new ResultPager($github);
            $repositories = $paginator->fetchAll($github->api('me'), 'repositories', ['all']);
        } catch (Exception $exception) {
            $githubException = 'Your Github API Token is invalid.';
        }

        $repositories = collect($repositories)->where('permissions.admin', true);

        return view('home')->with(compact('servers', 'repositories', 'forgeException', 'githubException'));
    }
}
