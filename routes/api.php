<?php

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\AuthenticationException;
use App\Rule;
use App\Vote;

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
    //vote = 1 (-1)
    $vote = $req->vote;

    $user = Auth()->user();

    //user_id = 1
    //1sa$kjwbdkajwdkjanjwadlw
    $user_hash =  hash("sha256", $user->id.$user->email);


    $rule = Rule::where('id', $rule_id)->first();

    //Guards
    $req->validate([
        'rule_id'=>'required|exists:rules,id',
    ]);
    
    //lkawdlk$#lajnlla
    $rule_hash = hash("sha256",$rule->id.$rule->title.$rule->desc);

    //Votes
    //id user_id rule_id
    //0  awjldal aqw;hqe

    //check if user voted for this rule before

    

    if(Vote::where('user_id',$user_hash)->where('rule_id',$rule_hash)->first()!==null){
        throw new Exception("User Already Voted");
    }

    if($vote != 1 && $vote != -1){
        throw new Exception("Vote can either be 1 or -1");
    }

    //Transaction to add in Rules votes and in votes table

    DB::beginTransaction();
    try{
        DB::table('votes')->insert([
            'user_id'=>$user_hash,
            'rule_id'=>$rule_hash,
        ]);

        //rule table
        if($vote==1){
            DB::table('rules')->where('id', $rule->id)->update([
                'votes_up'=> $rule->votes_up+1
            ]);
        }
        else{
            DB::table('rules')->where('id', $rule->id)->update([
                'votes_down'=> $rule->votes_down+1
            ]);
        }

        DB::commit();
        return response()->json(["success"=>true]);
    } catch (\Exception $e) {
        echo($e);
        // Rollback Transaction
        DB::rollback();
    }
});

Route::get("/rules", function(Request $req){
    return Rule::all();
});


function makeJWT($user){

    $header = json_encode(['alg'=>'bcrypt','type'=>'jwt']);

    $payload = json_encode(['nam'=>$user->name,'exp'=>Carbon::now()->addDays(30)->timestamp]);
    
    $secret = env('JWT_SECRET', Hash::make('JWT_SECRET'));

    $signature = Hash::make("{$header}.{$payload}.{$secret}");

    return $signature;
}