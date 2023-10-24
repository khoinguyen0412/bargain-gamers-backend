<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request,$username){
        $token = $request->cookie('authToken');

        $curr_id = null;

        if($token){
            $curr_id = PersonalAccessToken::findToken($token)->tokenable->id;
        }

        $user = User::where('username', $username)->first();
        $posts = User::withCount('posts')->find($user->id);


        if ($user){
            $profileInfo = [
                'allow_edit'=> false,
                'username'=> $user->username,
                'age'=> $user->age,
                'gender'=> $user->gender,
                'join_date'=> $user->created_at,
                'posts'=> $posts->posts_count
            ];
            if($curr_id == $user->id){
                $profileInfo['allow_edit'] = true;
                $profileInfo['email'] = $user->email;
                $profileInfo['location']=$user->location;
            }

            return responseData(config('apiconst.API_OK'),'',$profileInfo);
        }
        else{
            return responseData(config('apiconst.404_ERR_CODE'),'User not found');
        }
    }

    public function edit(Request $request, $username){
        $user = Auth::user();

        if($user->username == $username){
            try{
                $data=$request->all();
                $validCountries = Config::get('countries');
                
                $validator = Validator::make($data,[
                    'username'=>'nullable|string|unique:App\Models\User,username',
                    'age'=>'nullable|integer|min:0|max:100',
                    'gender'=>'nullable|integer|min:1|max:2',
                    'location'=>['nullable',Rule::in($validCountries[0])],
                ]);

                if ($validator->fails()){
                    return responseData(config('apiconst.INVALID_PARAM_CODE'),$validator->errors());
                }

                DB::beginTransaction();
                $user = User::where('username', $username)->first();

                $user['age'] = $data['age'] ? $data['age'] : $user['age'];
                $user['location'] = $data['location'] ? $data['location'] : $user['location'];
                $user['gender'] = $data['gender'] ? $data['gender'] : $user['gender'];
                $user->save();
                DB::commit();
                return responseData(config('apiconst.API_OK'),'Successfully edit data');  

            }catch(\Exception $e){
                DB::rollBack();
                return responseData(config('apiconst.INTERNAL_SERVER_ERROR_CODE'), $e->getMessage());
            } 
        }
        else{

        }
    }
    

    public function search(Request $request){
        $searchParam = $request->query('key');
        
        try{
            $list = User::where('username', 'LIKE', '%' . $searchParam . '%')->select('username','email')->get()->toArray();;
            $len_list = count($list);
            return responseData(config('apiconst.API_OK'),"Found $len_list results",$list);
        }catch(\Exception $e){
            return responseData(config('apiconst.INTERNAL_SERVER_ERROR_CODE'), $e->getMessage());
        }
       
    }
}
