<?php

namespace App\Microservice\Middleware;

use Closure;

class Preparation
{
    /**
     * Fill in a request with service information
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $toMerge = [];

        if($request->missing('msvc')){
            $toMerge['msvc'] = $request->route('microserviceName',env('MICROSERVICE_NAME'));
        }

        $pathParts = explode('/',$request->path());
        $performParts = array_slice($pathParts,2);
        $action = array_pop($performParts);
        $object = join('/',$performParts);

        $toMerge['msvc_object'] = $object;
        $toMerge['msvc_action'] = $action;


        $request->merge($toMerge);
        return $next($request);
    }
}
