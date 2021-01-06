<?php

namespace App\Microservice\Middleware;

use Closure;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Microservice\Tools\ErrorsGenerator;

class BasicValidation
{

    /**
     * Errors generator
     *
     * @var ErrorsGenerator
     */
    protected $errorsGenerator;

    /**
     * BasicValidation constructor.
     * @param ErrorsGenerator $errorGenerator
     */
    public function __construct(ErrorsGenerator $errorGenerator){
        $this->errorsGenerator = $errorGenerator;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param ErrorsGenerator $errorGenerator
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'msvc' => ['required','string','max:32'],
            ]
        );

        if($validator->fails()){
            return $this->errorsGenerator->fillResponse(Response::json([]),2);
        }

        return $next($request);
    }
}
