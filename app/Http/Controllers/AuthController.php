<?php

namespace App\Http\Controllers;

use App\Mail\VerificationMail;
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
     * @return JSONResponse
     */
    public function register(Request $request){
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
     * @return JSONResponse
     */
    public function login(Request $request){
        $credentials = $request->only(['email','password']);
        if(! $token = Auth::attempt($credentials)){
            return response()->json([
                'error' => 'Invalid Credentials',
            ],401);
        }
        return $this->tokenResponse($token);
    }


    /*
     * returns token array.
     * @param $token string
     * @return JSONResponse
     */
    protected function tokenResponse($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL()*60,
        ]);
    }


    /*
     * Veriifies new user
     * @return JSONresponse
     */
    public function userVerify(Request $request){
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
     * @return JSONResponse
     */
    public function logout(){
        Auth::logout();
        return response()->json([
            'message' => 'successfully logged out',
        ],200);
    }


    /*
     * returns logged in user.
     * @return JSONResponse
     */
    public function getAuthUser(){
        // $user = Auth::user();
        // dd($user);
        return response()->json([
            'user' => Auth::user(),
        ],200);
    }

}
