<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VerifyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $verification_code = Hash::make(str_random(10));
        $verify = VerifyUser::create([
            'user_id' => $user->user_id,
            'verification_code' => $verification_code,
        ]);
        /*
            code to send email with the $verification_code
        */

        return response()->json([
            'message' => 'User created successfully',
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
    public function userVerify($verification_code){
        
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
