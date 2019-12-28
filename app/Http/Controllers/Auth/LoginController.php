<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Token;
//use http\Env\Request;
use App\User;
use Carbon\Carbon;
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

    /**
     * override method
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View*
     */
    public function showLoginForm()
    {
        $attemptEndTime = session()->get('attempt_end_time');
        $now = Carbon::now();

        if(isset($attemptEndTime) && $attemptEndTime->diffInMinutes($now)<= 60){
            $timeLeft = $attemptEndTime->diffInMinutes($now);
            $timeLeft1 = 60 - $timeLeft;

            return redirect('/')->withErrors(["Try again after: " . $timeLeft1 . ' minutes']);
        }
        else{

            session()->forget('attempt_end_time');
            return view('auth.login');
        }
    }

    public function Login(Request $request)
    {
        $this->validateLogin($request);

       $user = User::where('email', $request->email)->first();

        if ($user->use_sms_verify == true) {

            $numberOfTry = 5;

            if ($user = app('auth')->getProvider()->retrieveByCredentials($request->only('email', 'password'))) {
                $token = Token::create([
                    'user_id' => $user->id
                ]);

                if ($token->sendCode()) {
                    session()->put("token_id", $token->id);
                    session()->put("user_id", $user->id);
                    session()->put("remember", $request->get('remember'));
                    session()->put("number_of_try", $numberOfTry);

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
            }else{
            //dd($request);
            $this->guard()->login($user, session()->get('remember', false));
            return redirect('home');
        }
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
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
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


            $count = session()->get('number_of_try');

            if($count > 1) {
                $count--;
                session()->put('number_of_try', $count);

                return redirect("code")->withErrors(["Invalid code." . ' Attempts left : ' . $count]);
            }
            else {
                session()->forget(['number_of_try']);
                session()->put('attempt_end_time', Carbon::now());

                return redirect('/')->withErrors(["The number of code entry attempts has ended. The following attempts will be possible after 1 hour."]);
            }
        }
        $token->used = true;
        $token->save();
        $this->guard()->login($token->user, session()->get('remember', false));

        session()->forget([
            'token_id', 'user_id', 'remember', 'number_of_try'
        ]);

        return redirect('home');
    }


}
