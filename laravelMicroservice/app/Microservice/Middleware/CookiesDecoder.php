<?php

namespace App\Microservice\Middleware;

use Closure;

class CookiesDecoder
{

    /**
     * Authentication tool
     * @var Authentication
     */
    protected $authenticationTool;

    public function __construct(\App\Microservice\Tools\Authentication $authentication){
        $this->authenticationTool = $authentication;

        // Add authentication cookie to json decode
        $this->json[] = $authentication->cookiesKey;
    }

    /**
     * Names of cookies for json decode
     *
     * @var array
     */
    protected $json = [

    ];

    /**
     * Attach cookies to Response
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        foreach ($request->cookies as $key=>$cookie){
            if(in_array($key,$this->json)){
                if(!empty($cookie)){
                    $cookie = json_decode($cookie);

                    $request->cookies->set(
                        $key, $cookie
                    );
                }
            }
        }
        return $next($request);
    }
}
