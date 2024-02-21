<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    protected string $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirectToProvider(): RedirectResponse
    {
        /** @var \Laravel\Socialite\Two\GithubProvider $driver */
        $driver = Socialite::driver('github');

        return $driver->setScopes(['user:email', 'repo'])->redirect();
    }

    public function handleProviderCallback(): RedirectResponse
    {
        /** @var \Laravel\Socialite\Two\User $social */
        $social = Socialite::driver('github')->user();

        $user = User::query()->firstOrNew(['github_id' => $social->getId()]);

        $user->fill([
            'name' => $social->getName(),
            'email' => $social->getEmail(),
            'github_id' => $social->getId(),
            'github_token' => $social->token,
        ]);

        $user->save();
        auth()->login($user);

        return redirect()->route('home');
    }
}
