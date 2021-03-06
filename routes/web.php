<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Http\Request;

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->group(['prefix' => 'api'], function() use ($app){
    $app->post('/users', function(Request $request){
        $this->validate($request,[
            'name'     => 'required|max:255',
            'email'    => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|max:16|confirmed'
        ]);
        $data = $request->all();
        $data['password'] = app('hash')->make($data['password']);
        $model = App\User::create($data);
        return response()->json($model,201);

    });

    $app->post('/login',function(Request $request){
        $this->validate($request,[
           'email' => 'required|email',
           'password' => 'required'
        ]);
        $email = $request->get('email');
        $password = $request->get('password');
        $user = \App\User::where('email','=',$email)->first();

        if(!$user || !\Hash::check($password,$user->password))
        {
            return response()->json(['message' => 'Invalid Credentials'],400);
        }

        $expiration = new \Carbon\Carbon();
        $expiration->addHour(2);
        $user->api_token = sha1(str_random(32)) .'.'.sha1(str_random(32));
        $user->api_token_expiration = $expiration->format('Y-m-d H:i:s');
        $user->save();

        return [
            'api_token' => $user->api_token,
            'api_token_expiration' => $user->api_token_expiration
        ];


    });

    $app->group(['middleware' => ['auth','token-expired']],function() use ($app){

        $app->get('/user-auth',function (Request $request){
            return $request->user();
        });

        $app->get('/clients',function(){
            return ['ok'];
        });

    });

});
