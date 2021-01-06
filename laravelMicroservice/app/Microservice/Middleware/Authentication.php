<?php

namespace App\Microservice\Middleware;

use App\Microservice\Tools\ErrorsGenerator;
use Closure;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class Authentication
{
    /**
     * Authentication tool
     * @var \App\Microservice\Tools\Authentication
     */
    protected $auth;

    /**
     * Errors generator
     *
     * @var ErrorsGenerator
     */
    protected $errorsGenerator;

    /**
     * Authentication constructor.
     * @param \App\Microservice\Tools\Authentication $authentication
     * @param ErrorsGenerator $errorGenerator
     */
    public function __construct(\App\Microservice\Tools\Authentication $authentication,ErrorsGenerator $errorGenerator){
        $this->auth = $authentication;
        $this->errorsGenerator = $errorGenerator;
    }

    /**
     * Authorize incoming request
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $validator = Validator::make(
            $request->all(),
            [
                // password
                'p' => ['alpha','min:4','max:64'],
                // username
                'u' => ['alpha','min:4','max:64'],
                // email
                'e' => ['email','max:128'],
                // token
                't' => ['alpha','min:32','max:128'],
                // remember flag
                'rm' => ['boolean'],
            ]
        );

        if($validator->fails()){
            return $this->errorsGenerator->fillResponse(Response::json([]),6);
        }

        $cookie = Cookie::get($this->auth->cookiesKey,false);
        $password = $request->input('p',false);
        $username = $request->input('u',false);
        $email = $request->input('e',false);
        $token = $request->input('t',false);
        $remember = $request->boolean('rm',false);


        $currentUser = $this->auth
            ->auth($cookie,$password,$username,$email,$token,$remember)
            ->getCurrentUser();

        $request->merge(['currentUser'=>$currentUser]);

        return $next($request);
    }
}
