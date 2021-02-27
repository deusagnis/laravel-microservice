<?php

namespace App\Microservice\Middleware;

use App\Microservice\Tools\ErrorsGenerator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class Mapping
{
    /**
     * Name of service connection
     *
     * @var string
     */
    protected $connectionName = 'msvc';
    /**
     * Table name for microservices map table
     *
     * @var string
     */
    protected $mapTableName = 'map';
    /**
     * Database facade
     *
     * @var DB
     */
    protected $db;

    /**
     * Errors generator
     *
     * @var ErrorsGenerator
     */
    protected $errorsGenerator;

    /**
     * Mapping constructor.
     *
     * @param DB $dbFacade
     * @param ErrorsGenerator $errorGenerator
     */
    public function __construct(DB $dbFacade,ErrorsGenerator $errorGenerator){
        $this->db = $dbFacade;
        $this->errorsGenerator = $errorGenerator;
    }

    /**
     * Map request to microservice
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $incomingMSVCName = $request->input('msvc');
        $currentMSVCName = env('MICROSERVICE_NAME');

        if($incomingMSVCName!=$currentMSVCName){
            $mapping = $this->db::connection($this->connectionName)->table($this->mapTableName)
                ->where('name','=',mb_strtolower($incomingMSVCName))
                ->first();
            if(!empty($mapping) and $mapping->redirect){
                return Response::redirectTo($mapping->address);
            }

            return $this->errorsGenerator->fillResponse(Response::json([]),5);
        }

        return $next($request);
    }
}
