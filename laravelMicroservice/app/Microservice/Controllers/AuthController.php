<?php

namespace App\Microservice\Controllers;

use App\Http\Controllers\Controller;
use App\Microservice\Tools\Authentication;
use App\Microservice\Tools\ErrorsGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register user action
     *
     * @param Request $request
     * @param Authentication $authentication
     * @param ErrorsGenerator $errorsGenerator
     * @return array
     */
    public function register(Request $request,Authentication $authentication,ErrorsGenerator $errorsGenerator){
        $result = [];

        $validator = Validator::make(
            $request->all(),
            [
                'password' => ['required','alpha','min:4','max:32'],
                'username' => ['required','alpha','min:4','max:64'],
                'email' => ['required','email','max:128'],
            ]
        );

        if($validator->fails()){
            return $errorsGenerator->createContent($result,4);
        }

        $result['result'] = $authentication->register(
            $request->input('password'),
            $request->input('username'),
            $request->input('email'),
        );

        return $result;
    }

    /**
     * Logout action
     *
     * @param Request $request
     * @param Authentication $authentication
     * @param ErrorsGenerator $errorsGenerator
     * @return array
     */
    public function logout(Request $request,Authentication $authentication,ErrorsGenerator $errorsGenerator){
        $result = [];

        if(empty($request->input('currentUser'))){
            return $errorsGenerator->createContent($result,3);
        }

        $authentication->logout();

        $result['result'] = 1;

        return $result;
    }

    /**
     * Login action
     *
     * @param Request $request
     * @param Authentication $authentication
     * @param ErrorsGenerator $errorsGenerator
     * @return array
     */
    public function login(Request $request,Authentication $authentication,ErrorsGenerator $errorsGenerator){
        $result = [];

        $validator = Validator::make(
            $request->all(),
            [
                'password' => ['alpha','min:4','max:32'],
                'username' => ['alpha','min:4','max:64'],
                'email' => ['email','max:128'],
                'token' => ['string','min:32','max:128'],
                'remember' => ['boolean'],
            ]
        );

        if($validator->fails()){
            return $errorsGenerator->createContent($result,4);
        }

        $password = $request->input('password',false);
        $username = $request->input('username',false);
        $email = $request->input('email',false);
        $token = $request->input('token',false);
        $remember = $request->boolean('remember',true);

        $result['result'] = $authentication
            ->login($password,$username,$email,$token,$remember)
            ->authorized();

        return $result;
    }

    /**
     * Create token action
     *
     * @param Request $request
     * @param Authentication $authentication
     * @param ErrorsGenerator $errorsGenerator
     * @return array
     */
    public function createToken(Request $request,Authentication $authentication,ErrorsGenerator $errorsGenerator){
        $result = [];

        if(empty($request->input('currentUser'))){
            return $errorsGenerator->createContent($result,3);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'expired' => ['integer','max:32'],
            ]
        );

        if($validator->fails()){
            return $errorsGenerator->createContent($result,4);
        }

        $result['result'] = $authentication->createToken($request->input('expired',0));

        return $result;
    }

    /**
     * Get tokens action
     *
     * @param Request $request
     * @param Authentication $authentication
     * @param ErrorsGenerator $errorsGenerator
     * @return array
     */
    public function getTokens(Request $request,Authentication $authentication,ErrorsGenerator $errorsGenerator){
        $result = [];

        if(empty($request->input('currentUser'))){
            return $errorsGenerator->createContent($result,3);
        }

        $first = $request->boolean('first',false);

        $result['result'] = $authentication->getTokens($first);

        return $result;
    }

}
