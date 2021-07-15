<?php

namespace App\Http\Controllers;

use App\Mail\PasswordReset;
use App\Mail\VerificationMail;
use App\Models\PasswordChange;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /*
     * Registers a user.
     * @return JSON response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|between:2,60',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6',
        ]);
        if($validator->fails()){
            return response()->json(['errors'=>$validator->errors()->all()],422);
        }
        $code = str_replace('.','',urlencode(str_replace('/','',Hash::make(str_random(10)))));      
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verification_code'=> $code,
        ]);
        Mail::to($request->email)->send(new VerificationMail($user,$code));
        return response()->json([
            'message' => 'User created, please click verification link on mail to activate account',
            'user' => $user,
        ],201);
    }


    /*
     * Login a user and return a JWT token.
     * @return JSON response
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['email','password']);
        if(! $token = Auth::attempt($credentials)){
            return response()->json([
                'error' => 'Invalid Credentials',
            ],401);
        }
        $user = User::where('email',$request->email) -> first();
        if($user->verified == 0){
            return response()->json([
                'error' => 'User not verified. Please click the verification link sent to you via email',
            ],401);
        }
        return $this->tokenResponse($token);
    }


    /*
     * returns token array.
     * @param $token string
     * @return JSON response
     */
    protected function tokenResponse($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL()*60,
        ]);
    }


    /*
     * Veriifies new user
     * @return JSON response
     */
    public function userVerify(Request $request)
    {
        $code = $request->code;
        $user = User::where('verification_code', '=', $code)->first();
        if($user === null){
            return response()->json([
                'message' => 'Invalid'
            ],401);
        }
        $user->verified = true;
        $user->verification_code = NULL;
        $user->save();
        return response()->json([
            'message'=>'user verified successfully'
        ]);
    }


    /*
     * logs out a user.
     * @return JSON response
     */
    public function logout()
    {
        Auth::logout();
        return response()->json([
            'message' => 'successfully logged out',
        ],200);
    }


    /*
     * returns logged in user.
     * @return JSON response
     */
    public function getAuthUser()
    {
        return response()->json([
            'user' => Auth::user(),
        ],200);
    }


    /*
     * sends mail to reset password
     * @return JSON response
     */
    public function resetPasswordMail(Request $request)
    {
        $user = User::where('email',$request->email) -> first();
        if(!($user->exists())){
            return response()->json([
                'message' => 'user with this email does not exist',
            ],401);
        }
        if($user->verified){
            return response()->json([
                'message'=>'account not yet verified. Please click email verification link in your email.'
            ],401);
        }
        $token = str_replace('.','',urlencode(str_replace('/','',Hash::make(str_random(10)))));      
        PasswordChange::create([
            'email' => $request->email,
            'token'=> $token,
        ]);
        Mail::to($request->email)->send(new PasswordReset($token,$request->email));
        return response()->json([
            'message' => 'Password reset link has been sent to your email',
        ],201);

    }

    /*
     * Verifies token for password change
     * @return JSON response
     */
    public function checkPasswordToken(Request $request)
    {
        $code = $request->code;
        $pass_change = PasswordChange::where('token', $code)->first();
        if($pass_change === null){
            return response()->json([
                'message' => 'Invalid'
            ],401);
        }
        return response()->json([
            'message'=>'password token verified. Renders new password view',
            'token' => $code,
        ],200);
    }


    /*
     * Verifies token and resets password in Database
     * @return JSON response
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'new_password' => 'required|min:6',
            'token' => 'required',
        ]);
        if($validator->fails()){
            return response()->json(['errors'=>$validator->errors()->all()],422);
        }
        $pass_change = PasswordChange::where('token',$request->token)->first();
        if(!$pass_change){
            return response()->json([
                'message' => 'invalid token'
            ],401);
        }
        $user = User::where('email',$pass_change->email) -> first();
        $user->password = Hash::make($request->new_password);
        $pass_change->token = NULL;
        $pass_change->save();
        $user->save();
        return response()->json([
            'message' => 'password changed successfully'
        ],200);
    }

}
