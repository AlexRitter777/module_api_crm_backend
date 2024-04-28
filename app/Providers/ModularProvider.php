<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModularProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        $modules = config('modular.modules'); //modules array
        $path = config('modular.path'); // base_path() . '/app/Modules'

        if($modules){

            Route::prefix('')->group(function() use ($modules, $path){

                foreach ($modules as $mod => $submodules){ //Admin => [User, ...]
                    foreach ($submodules as $key=>$submodule) { //keys currently doesn't use
                        $relativePath = "/$mod/$submodule"; // Admin/User

                        Route::middleware('web')->group(function() use ($mod, $submodule, $relativePath, $path){

                            $this->getWebRoutes($mod, $submodule, $relativePath, $path);

                        });

                        Route::prefix('api')->middleware('api')
                            ->group(function () use ($mod, $submodule, $relativePath, $path){

                                $this->getApiRoutes($mod, $submodule, $relativePath, $path);

                        });

                    }
                }

            });

        }

    }

    private function getWebRoutes(string $mod, string $submodule, string $relativePath, string $path) :void
    {
        $routesPath = $path . $relativePath . '/Routes/web.php'; // base_path() . '/app/Modules/Admin/User/Routes/web.php
        if(file_exists($routesPath)){

            if($mod != config('modular.groupWithoutPrefix')){
                Route::group(
                    [
                        'prefix' => strtolower($mod),
                        'middleware' => $this->getMiddleware($mod)
                    ],

                    function () use ($mod, $submodule, $routesPath) {
                       Route::namespace("App\Modules\\$mod\\$submodule\Controllers")->group($routesPath);
                    });

            }else{

                Route::namespace("App\Modules\\$mod\\$submodule\Controllers")->middleware($this->getMiddleware($mod))
                    ->group($routesPath);

            }

        }

    }

    private function getApiRoutes(string $mod, string $submodule, string $relativePath, string $path) :void
    {
        $routesPath = $path.$relativePath.'/Routes/api.php';
        if(file_exists($routesPath)) {
            Route::group(
                [
                    'prefix' => strtolower($mod),
                    'middleware' => $this->getMiddleware($mod, 'api')
                ],
                function() use ($mod, $submodule, $routesPath) {
                    Route::namespace("App\Modules\\$mod\\$submodule\Controllers")->
                    group($routesPath);
                }
            );
        }

    }

    private function getMiddleware(string $mod, string $key = 'web') : array
    {
        $middleware = [];

        $config = config('modular.groupMiddleware');

        if(isset($config[$mod])){
            if (array_key_exists($key, $config[$mod])){
                $middleware = array_merge($middleware, $config[$mod][$key]);
            }
        }

        return $middleware;

    }
}
