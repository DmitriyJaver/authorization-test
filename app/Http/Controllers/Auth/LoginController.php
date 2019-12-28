<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Token;
//use http\Env\Request;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Session;
use Illuminate\Session\Store;

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

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function Login(Request $request)
    {
        $this->validateLogin($request);

        //retrieveByCredentials
        if ($user = app('auth')->getProvider()->retrieveByCredentials($request->only('email', 'password'))) {
            $token = Token::create([
                'user_id' => $user->id
            ]);

            if ($token->sendCode()) {
                session()->put("token_id", $token->id);
                session()->put("user_id", $user->id);
                session()->put("remember", $request->get('remember'));

                return redirect("code");
            }

            $token->delete();// delete token because it can't be sent
            return redirect('/login')->withErrors([
                "Unable to send verification code"
            ]);
        }

        return redirect()->back()
            ->withInputs()
            ->withErrors([
                $this->username() => Lang::get('auth.failed'),
            ]);
    }

    public function showCodeForm()
    {
        if (! session()->has("token_id")) {
            return redirect("login");
        }

        return view("auth.code");
    }

    /**
     * Store and verify user second factor.
     */
    public function storeCodeForm(Request $request)
    {
        // throttle for too many attempts
        if (! session()->has("token_id", "user_id")) {
            return redirect("login");
        }

        $token = Token::find(session()->get("token_id"));
        if (! $token ||
            ! $token->isValid() ||
            $request->code !== $token->code ||
            (int)session()->get("user_id") !== $token->user->id
        ) {
            return redirect("code")->withErrors(["Invalid token"]);
        }

        $token->used = true;
        $token->save();
        $this->guard()->login($token->user, session()->get('remember', false));

        session()->forget('token_id', 'user_id', 'remember');

        return redirect('home');
    }
}
