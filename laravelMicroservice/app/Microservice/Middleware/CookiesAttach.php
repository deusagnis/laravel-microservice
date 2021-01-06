<?php

namespace App\Microservice\Middleware;

use Closure;
use Illuminate\Support\Facades\Cookie;

class CookiesAttach
{

    /**
     * Cookie facade
     *
     * @var Cookie
     */
    protected $cookiesFacade;

    public function __construct(Cookie $cookiesFacade){
        $this->cookiesFacade = $cookiesFacade;
    }

    /**
     * Attach cookies to Response
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $cookies = $this->cookiesFacade::getQueuedCookies();

        foreach ($cookies as $cookie){
            $response->withCookie($cookie);
        }

        return $response;
    }
}
