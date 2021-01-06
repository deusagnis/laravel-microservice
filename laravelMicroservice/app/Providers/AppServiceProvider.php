<?php

namespace App\Providers;

use App\Microservice\Tools\Authentication;
use App\Microservice\Tools\ErrorsGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Responsible for generation named errors
         */
        $this->app->singleton(ErrorsGenerator::class,function (){
            return new ErrorsGenerator();
        });
        /**
         * Responsible for authentication
         */
        $this->app->singleton(Authentication::class,function ($app){
            return new Authentication(
                $app->make('Illuminate\Support\Facades\DB'),
                $app->make('Illuminate\Support\Facades\Cookie')
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
