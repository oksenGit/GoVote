<?php

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\AuthenticationException;
use App\Rule;
use App\Vote;
use App\Option;
use Mockery\Exception;
use Illuminate\Support\Facades\Crypt;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/user/login', function(Request $req){
    $req->validate([
        'email'=>'required|email',
        'password'=>'required'                
    ]);

    if(Auth::attempt(['email'=>$req->email,'password'=>$req->password])){
        $user = Auth::user();
        $jwt = makeJWT($user);
        $user->api_token = $jwt;
        $user->save();
        return $user;
    }
    throw new AuthenticationException;
});


Route::middleware('auth:api')->post('/user/changePassword', function(Request $req){
    $user = Auth::user();
    $password = $req->password;
    $newPassword = $req->newPassword; 
    $isValid = Hash::check($password,$user->getAuthPassword());
    if($isValid){
        $user->password = Hash::make($newPassword);
        $user->api_token = "";
        $user->save();
        return response()->json(["success"=>true]);
    }
    throw new AuthenticationException;
});


Route::middleware('auth:api')->post('rules/add', function (Request $req){
    $user = Auth()->user();
    $title = $req->title;
    $desc =  $req->desc;
    $start_time = $req->start_time;
    $end_time = $req->end_time;
    $options = $req->options;
    $options = json_decode($options);

    $rule = new Rule();
    $rule->title = $title;
    $rule->desc = $desc ;
    $rule->user_id = $user->id ;
    $rule->start_time = $start_time;
    $rule->end_time = $end_time;
    $rule->save();
    $rule->fresh();
    
    foreach($options as $o){
        $s = new Option();
        $s->rule_id = $rule->id;
        $s->name = $o;
        $rule->options()->save($s);
    }
    
    return $rule->fresh();
});


Route::middleware('auth:api')->post('user/logout', function (Request $req){
    $user = Auth()->user();
    $user->api_token = "";
    $user->save();
    return response()->json(["success"=>true]);
});

Route::middleware('auth:api')->post("user/vote", function(Request $req){
    //Vars
    //rule_id = 15
    $rule_id = $req->rule_id;
    
    //option_id
    $option_id = $req->option_id;

    $user = Auth()->user();

    //user_id = 1
    //1sa$kjwbdkajwdkjanjwadlw
    $user_hash =  hash("sha256", $user->id.$user->email);


    $rule = Rule::where('id', $rule_id)->first();

    //Guards
    $req->validate([
        'rule_id'=>'required|exists:rules,id',
    ]);

    $options = $rule->options()->get();
    
    //lkawdlk$#lajnlla
    $rule_hash = hash("sha256",$rule->id.$rule->title.$rule->desc);

    //Votes
    //id user_id rule_id
    //0  awjldal aqw;hqe

    //check if user voted for this rule before

    if(Vote::where('user_id',$user_hash)->where('rule_id',$rule_hash)->first()!==null){
        throw new Exception("User Already Voted");
    }

    if(!Carbon::now()->isBetween(Carbon::parse($rule->start_time),Carbon::parse($rule->end_time))){
        throw new Exception("Voting Rejected");
    }

    $contaisOption = false;
    foreach($options as $op){
        if($op->id == $option_id){
            $contaisOption = true;
            break;
        }
    }

    if($contaisOption==false){
        throw new Exception("Option Doesn't Exist");
    }


    //Transaction to add in Rules votes and in votes table

    DB::beginTransaction();
    try{
        DB::table('votes')->insert([
            'user_id'=>$user_hash,
            'rule_id'=>$rule_hash,
        ]);

        
        //get option of option_id
        //decrypt count
        //add one to count
        //encrypt count
        //save


        $option = $options->where("id",$option_id)->first();
        $count = Crypt::decrypt($option->count);
        $count = $count + 1;
        $count = Crypt::encrypt($count);
        
        DB::table('options')->where('id', $option->id)->update([
            'count'=>$count
        ]);
        
        DB::commit();
        return response()->json(["success"=>true]);
    } catch (\Exception $e) {
        echo($e);
        // Rollback Transaction
        DB::rollback();
    }
});

Route::get("/rules", function(Request $req){
    $rules =  Rule::with("options")->get();
    $rulesList = [];
    foreach($rules as $rule){
        $rule = json_encode($rule);
        $rule = json_decode($rule);//DON'T DELETE THIS PALEZ
        if(Carbon::now()->isAfter(Carbon::parse($rule->end_time))){
            $options = $rule->options;
            foreach($options as &$op){
                $op->count = Crypt::decrypt($op->count)+0;
            }
        }
        array_push($rulesList, $rule);
    }
    return $rulesList;
});




function makeJWT($user){

    $header = json_encode(['alg'=>'bcrypt','type'=>'jwt']);

    $payload = json_encode(['nam'=>$user->name,'exp'=>Carbon::now()->addDays(30)->timestamp]);
    
    $secret = env('JWT_SECRET', Hash::make('JWT_SECRET'));

    $signature = Hash::make("{$header}.{$payload}.{$secret}");

    return $signature;
}