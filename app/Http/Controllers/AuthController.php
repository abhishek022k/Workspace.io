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
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'name' => 'required|string|between:2,60',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->all()], 422);
        }
        if (!$this->validateCaptcha($request->token)) {
            return response()->json(['message' => ['Captcha Invalid. Please retry or contact owner if issue persists']], 422);
        }
        $code = str_replace('.', '', urlencode(str_replace('/', '', str_random(10))));
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verification_code' => $code,
        ]);
        $unique_code = $code . 'P' . $user->id;
        Mail::to($request->email)->send(new VerificationMail($user, $unique_code));
        return response()->json([
            'message' => 'User created, please click verification link on mail to activate account',
            'user' => $user
        ], 201);
    }

    /*
     * Validates google recaptcha token recieved from signup form
     * @return boolean 
     */
    private function validateCaptcha($token)
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . env('CAPTCHA_SECRET') . '&response=' . $token;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch), true);
        return $result['success'];
    }


    /*
     * Login a user and return a JWT token.
     * @return JSON response
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);
        if (!$token = Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid Credentials',
            ], 401);
        }
        $user = User::where('email', $request->email)->first();
        if ($user->verified == 0) {
            return response()->json([
                'message' => 'User not verified. Please click the verification link sent to you via email',
            ], 401);
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
            'expires_in' => Auth::factory()->getTTL() * 60,
        ]);
    }


    /*
     * Veriifies new user
     * @return JSON response
     */
    public function userVerify(Request $request)
    {
        $code = $request->code;
        $b = strrpos($code, "P");
        $uid = substr($code, $b + 1);
        $code = substr($code, 0, $b);
        $user = User::where('verification_code', '=', $code)
            ->where('id', '=', $uid)->get()->first();
        if ($user === null) {
            return response()->json([
                'message' => 'Invalid'
            ], 401);
        }
        $user->verified = true;
        $user->verification_code = NULL;
        $user->save();
        return response()->json([
            'message' => 'User verified successfully, you can now login'
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
        ], 200);
    }


    /*
     * returns logged in user.
     * @return JSON response
     */
    public function getAuthUser()
    {
        $user = User::find(Auth::user()->id)->makeVisible(['admin_access']);
        return response()->json([
            'user' => $user,
        ], 200);
    }


    /*
     * sends mail to reset password
     * @return JSON response
     */
    public function resetPasswordMail(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!($user->exists())) {
            return response()->json([
                'message' => 'user with this email does not exist',
            ], 401);
        }
        if (!($user->verified)) {
            return response()->json([
                'message' => 'account not yet verified. Please click email verification link in your email.'
            ], 401);
        }
        $token = str_replace('.', '', urlencode(str_replace('/', '', str_random(10))));
        $date = (new \DateTime())->modify('+3 day')->format('Y-m-d H:i:s');
        $pass = PasswordChange::create([
            'user_id' => $user->id,
            'token' => $token,
            'expiry_date' => $date
        ]);
        $unique_token = $token . 'P' . $pass->id;

        Mail::to($request->email)->send(new PasswordReset($unique_token, $request->email));
        return response()->json([
            'message' => 'Password reset link has been sent to your email',
        ], 201);
    }

    /*
     * Verifies token for password change
     * @return JSON response
     */
    public function checkPasswordToken(Request $request)
    {
        $code = $request->code;
        $b = strrpos($code, "P");
        $uid = substr($code, $b + 1);
        $token = substr($code, 0, $b);
        $pass_change = PasswordChange::where('token', $token)->where('id', '=', $uid)->first();
        if ($pass_change === null) {
            return response()->json([
                'message' => 'Invalid'
            ], 401);
        }
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        if ($now > $pass_change->expiry_date) {
            return response()->json([
                'message' => 'Token expired. Please make a new request for password reset.'
            ], 498);
        }
        return response()->json([
            'message' => 'password token verified. Renders new password view',
            'token' => $code,
        ], 200);
    }


    /*
     * Verifies token and resets password in Database
     * @return JSON response
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|min:6',
            'token' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->all()], 422);
        }
        $code = $request->token;
        $b = strrpos($code, "P");
        $uid = substr($code, $b + 1);
        $code = substr($code, 0, $b);
        $pass_change = PasswordChange::where('token', $code)->where('id', '=', $uid)->first();
        if ($pass_change === null) {
            return response()->json([
                'message' => 'Invalid'
            ], 401);
        }
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        if ($now > $pass_change->expiry_date) {
            return response()->json([
                'message' => 'Token expired'
            ], 498);
        }
        $user = User::where('id', $pass_change->user_id)->first();
        $user->password = Hash::make($request->new_password);
        $pass_change->delete();
        $user->save();
        return response()->json([
            'message' => 'password changed successfully'
        ], 200);
    }
}
