<?php


namespace App\Microservice\Tools;


use Illuminate\Support\Facades\Response;

class ErrorsGenerator
{
    /**
     * List of existing  errors: code => name
     * @var string[]
     */
    protected $errors = [
        0 => 'Unexpected error',
        1 => 'API not found',
        2 => 'Basic validation failed',
        3 => 'Authentication failed',
        4 => 'Method validation failed',
        5 => 'Microservice not found',
        6 => 'Authentication validation failed',
    ];

    /**
     * Creation formatted errors array
     * @param mixed $errors
     * @return array
     */
    protected function genErrors($errors){
        if(is_numeric($errors) or empty($errors)){
            $errors = $this->getErrorByCode($errors);
        }elseif (is_string($errors)){
            $errors = [0 => $errors];
        }
        $formatted = [];
        foreach ($errors as $code=>$value){
            $formatted[] = [
                'code' => $code,
                'value' => $value
            ];
        }
        return $formatted;
    }

    /**
     * Create array of single error by code
     * @param $code
     * @return string[]
     */
    protected function getErrorByCode($code){
        if(!isset($this->errors[$code])){
            $code = 0;
        }
        return [$code => $this->errors[$code]];
    }

    /**
     * Create content array for response
     * @param $content
     * @param $errors
     * @return array|mixed
     */
    public function createContent($content,$errors){
        if(is_array($content)){
            if(!isset($content['errors'])){
                $content['errors']=[];
            }
        }else{
            $content = [
                'result' => $content,
                'errors' => [],
            ];
        }
        $generatedErrors = $this->genErrors($errors);
        $content['errors'] = array_merge($content['errors'],$generatedErrors);

        return $content;
    }

    /**
     * Create JSON response for errors
     * @param $response
     * @param $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function fillResponse($response,$errors){
        $content = $response->getOriginalContent();

        $content = $this->createContent($content,$errors);

        return Response::json($content,$response->status(),$response->headers->all());
    }
}
