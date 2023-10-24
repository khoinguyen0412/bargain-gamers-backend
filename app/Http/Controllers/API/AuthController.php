<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    //
    public function register(Request $request){
        try{
            $data = $request->all();
            DB::beginTransaction();
            $validator = Validator::make($data,[
                'username' => 'required|string|unique:App\Models\User,username',
                'email'=> 'required|email|unique:App\Models\User,email',
                // 'password' => 'required|string|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
                'password' => ['required',
                                'string',
                                'min:6',
                                function ($attribute, $value, $fail) {
                                    $patterns = [
                                        '/[A-Z]/' => 'uppercase letter',
                                        '/[a-z]/' => 'lowercase letter',
                                        '/[0-9]/' => 'digit',
                                        '/[@#$%^&+=]/' => 'special character',
                                    ];
                        
                                    foreach ($patterns as $pattern => $patternDescription) {
                                        if (!preg_match($pattern, $value)) {
                                            $fail("The password must contain at least one $patternDescription.");
                                        }
                                    }
                                },]
            ],
         
            );

            if ($validator->fails()){
                return responseData(config('apiconst.INVALID_PARAM_CODE'),$validator->errors());
            }

            $user = new User();
            $user->username = $data['username'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->save();
            DB::commit();

            return responseData(config('apiconst.API_OK'),config('apiconst.API_OK_MESS'));
        }
        catch(\Exception $e){
            DB::rollBack();
            return responseData(config('apiconst.INTERNAL_SERVER_ERROR_CODE'), $e->getMessage());
        }
    }

    public function unauthorized(){
        return responseData(config('apiconst.UNAUTHORIZED_CODE'),config('apiconst.UNAUTHORIZED_MESS'));
    }

    public function login(Request $request){
        
        $data = $request->all();
        $validator = Validator::make($data,[
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()){
            return responseData(config('apiconst.INVALID_PARAM_CODE'),$validator->errors());
        }

        // Find the user by the input parameters
        $user = User::where('username', $request->username)->first();


        if($user){
            if(Hash::check($data['password'], $user->password)){
                $expirationTime = now()->addDay();
                $token = $user->createToken('web-token',expiresAt:$expirationTime)->plainTextToken;
                $exp_time=$expirationTime->format('Y-m-d\TH:i:s\Z');
                $expiration_time = $expirationTime->toDateTime()->getTimestamp();
                $authUser = $request->username;
                $cookie = setcookie('authToken',$token,$expiration_time,httponly:true,path:"/",secure:true);
                return responseData(config('apiconst.API_OK'),'Succesfully Log In',['token'=>$token,'authUser'=>$authUser,'expirationTime'=>$exp_time]);
            }
            else{
                return responseData(config('apiconst.INVALID_PARAM_CODE'),'Wrong Password Entered');
            }
        }
        else{
            return responseData(config('apiconst.INVALID_PARAM_CODE'),'User does not exist');
        }
    }

    public function validateToken(Request $request){
        $token = $request->cookie('authToken');

        if(!$token){
            return responseData(config('apiconst.INTERNAL_SERVER_ERROR_CODE'),"No token sent with cookies",['authenticated' => 'false']); ;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if(!$accessToken || $accessToken->expires_at->isPast()){
            return responseData(config('apiconst.INVALID_TOKEN_CODE'),"Invalid token",['authenticated' => 'false']);
        }

        return responseData(config('apiconst.API_OK'),"Valid token",['authenticated' => 'true']);

    }

    public function logout(){
        Auth::user()->currentAccessToken()->delete();
        return responseData(config('apiconst.API_OK'), "Successfully logged out")->withCookie(cookie()->forget('authToken'));
    }
}
