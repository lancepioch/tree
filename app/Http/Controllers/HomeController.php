<?php

namespace App\Http\Controllers;

use Exception;
use Github\Client;
use Github\ResultPager;
use Illuminate\View\View;
use Laravel\Forge\Forge;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index(Forge $forge): View
    {
        $githubException = null;
        $forgeException = null;
        $servers = [];
        $repositories = [];

        if (is_null(auth()->user()->forge_token)) {
            $forgeException = 'Please enter a valid Forge API Token from: <a href="https://forge.laravel.com/user-profile/api">Laravel Forge > Account > API</a>';

            return view('home')->with(compact('servers', 'repositories', 'forgeException', 'githubException'));
        }

        try {
            $forge = $forge->setApiKey(auth()->user()->forge_token, null);
            $servers = array_reverse($forge->servers());
        } catch (Exception) {
            $forgeException = 'Your Forge API Token is invalid.';
        }

        try {
            $github = new Client();
            $github->authenticate(auth()->user()->github_token, null, Client::AUTH_ACCESS_TOKEN);

            $paginator = new ResultPager($github);
            $repositories = $paginator->fetchAll($github->api('me'), 'repositories', ['all']);
        } catch (Exception) {
            $githubException = 'Your Github API Token is invalid.';
        }

        $repositories = collect($repositories)->where('permissions.admin', true);

        return view('home')->with(compact('servers', 'repositories', 'forgeException', 'githubException'));
    }
}
