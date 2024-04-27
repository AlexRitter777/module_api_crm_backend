<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ModulMake extends Command
{

    private Filesystem $files;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name}
                                                    {--all}
                                                    {--migration}
                                                    {--vue}
                                                    {--view}
                                                    {--controller}
                                                    {--model}
                                                    {--api}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';




    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->files = $filesystem;
    }


    /**
     * Execute the console command.
     */
    public function handle()
    {
        if($this->option('all')) {
            $this->input->setOption('migration', true);
            $this->input->setOption('vue', true);
            $this->input->setOption('view', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('model', true);
            $this->input->setOption('api', true);

        }

        if($this->option('model')) {
            $this->createModel();
        }
        if($this->option('controller')) {
            $this->createController();
        }
        if($this->option('api')) {
            $this->createApiController();
        }
        if($this->option('migration')) {
            $this->createMigration();
        }
        if($this->option('vue')) {
            $this->createVueComponent();
        }
        if($this->option('view')) {
            $this->createView();
        }

    }


    /**
     * Creates a new Eloquent model class in a specific module.
     */
    private function createModel() : void
    {

        $model = Str::singular(Str::studly(class_basename($this->argument('name'))));
        $this->call('make:model', [
           'name' => "App\\Modules\\" . trim($this->argument('name')) . "\\Models\\" . $model
        ]);

    }


    /**
     * Creates a new controller class and routes for a specific module.
     */
    private function createController() : void
    {
        $controller = Str::studly(class_basename($this->argument('name'))); //controller's name
        $modelName = Str::singular(Str::studly(class_basename($this->argument('name')))); //Model's name

        $path = $this->getControllerPath($this->argument('name'));

        if($this->alreadyExists($path)){
            $this->error('Controller already exists!');
        } else {

            $this->makeDirectory($path);

            $stub = $this->files->get(base_path('resources/stubs/controller.model.api.stub'));

            $stub = str_replace(
                [
                    'DummyNamespace',
                    'DummyRootNamespace',
                    'DummyClass',
                    'DummyFullModelClass',
                    'DummyModelClass',
                    'DummyModelVariable'
                ],
                [
                    "App\\Modules\\" . trim($this->argument('name')) . "\\Controllers",
                    $this->laravel->getNamespace(),
                    $controller.'Controller',
                    "App\\Modules\\" . trim($this->argument('name')) . "\\Models\\{$modelName}",
                    $modelName,
                    lcfirst($modelName)
                ],
                $stub
            );
            $this->files->put($path, $stub);
            $this->info('Controller created successfully!');
            //$this->updateModularConfig();


        }
        $this->createRoutes($controller, $modelName);



    }


    /**
     * Creates a new api controller class and api routes for a specific module.
     */
    private function createApiController()
    {

        $controller = Str::studly(class_basename($this->argument('name'))); //controller's name
        $modelName = Str::singular(Str::studly(class_basename($this->argument('name')))); //Model's name

        $path = $this->getApiControllerPath($this->argument('name'));

        if($this->alreadyExists($path)){
            $this->error('Controller already exists!');
        } else {

            $this->makeDirectory($path);

            $stub = $this->files->get(base_path('resources/stubs/controller.model.api.stub'));

            $stub = str_replace(
                [
                    'DummyNamespace',
                    'DummyRootNamespace',
                    'DummyClass',
                    'DummyFullModelClass',
                    'DummyModelClass',
                    'DummyModelVariable'
                ],
                [
                    "App\\Modules\\" . trim($this->argument('name')) . "\\Controllers",
                    $this->laravel->getNamespace(),
                    $controller . 'Controller',
                    "App\\Modules\\" . trim($this->argument('name')) . "\\Models\\{$modelName}",
                    $modelName,
                    lcfirst($modelName)
                ],
                $stub
            );
            $this->files->put($path, $stub);
            $this->info('Controller created successfully.');
            //$this->updateModularConfig();
        }

        $this->crateApiRoutes($controller, $modelName);

    }

    /**
     * Creates a new database migration for a specific module.
     */
    private function createMigration() : void
    {

        $table = Str::plural(Str::snake(class_basename($this->argument('name'))));
        try {
            $this->call('make:migration', [
                'name' => "create_{$table}_table",
                '--create' => $table,
                '--path' => "App\\Modules\\" . trim($this->argument('name')) . "\\Migrations"
            ]);

        }catch (\Exception $e){

            $this->error($e->getMessage());
        }

    }

    /**
     * Creates a new vue.js component for a specific module.
     */
    private function createVueComponent()
    {

        $path = $this->getVueComponentPath($this->argument('name'));

        $component = Str::studly(class_basename($this->argument('name')));

        if ($this->alreadyExists($path)) {
            $this->error('Vue Component already exists!');
        } else {
            $this->makeDirectory($path);

            $stub = $this->files->get(base_path('resources/stubs/vue.component.stub'));

            $stub = str_replace(
                [
                    'DummyClass',
                ],
                [
                    $component,
                ],
                $stub
            );

            $this->files->put($path, $stub);
            $this->info('Vue Component created successfully.');
        }

    }

    /**
     * Creates a new view file for a specific module.
     */
    private function createView()
    {

        $paths = $this->getViewPath($this->argument('name'));

        foreach ($paths as $path) {
            $view = Str::studly(class_basename($this->argument('name')));

            if ($this->alreadyExists($path)) {
                $this->error('View already exists!');
            } else {
                $this->makeDirectory($path);

                $stub = $this->files->get(base_path('resources/stubs/view.stub'));

                $stub = str_replace(
                    [
                        '',
                    ],
                    [
                    ],
                    $stub
                );

                $this->files->put($path, $stub);
                $this->info('View created successfully.');
            }
        }

    }

    /**
     * Generates the full path for a controller based on the module name.
     *
     * @param string $name
     * @return string
     */
    private function getControllerPath(string $name) : string
    {

        $controller = Str::studly(class_basename($name));
        return $this->laravel['path'] . '/Modules/' . str_replace('\\', '/', $name) . "/Controllers/" . "{$controller}Controller.php";

    }


    /**
     * Generates the full path for an api controller based on the module name.
     *
     * @param string $name
     * @return string
     */
    private function getApiControllerPath(string $name) : string
    {

        $controller = Str::studly(class_basename($name));
        return $this->laravel['path'] . '/Modules/' . str_replace('\\', '/', $name) . "/Controllers/Api/" . "{$controller}Controller.php";

    }

    private function makeDirectory(string $path)
    {

        if(!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;

    }

    private function createRoutes(string $controller, string $modelName)
    {

        $routePath = $this->getRoutesPath($this->argument('name'));

        if ($this->alreadyExists($routePath)) {
            $this->error('Routes already exists!');
        } else {

            $this->makeDirectory($routePath);

            $stub = $this->files->get(base_path('resources/stubs/routes.web.stub'));

            $stub = str_replace(
                [
                    'DummyClass',
                    'DummyRoutePrefix',
                    'DummyModelVariable',
                ],
                [
                    $controller.'Controller',
                    Str::plural(Str::snake(lcfirst($modelName), '-')),
                    lcfirst($modelName)
                ],
                $stub
            );

            $this->files->put($routePath, $stub);
            $this->info('Routes created successfully.');
        }


    }

    private function crateApiRoutes(string $controller, string $modelName)
    {

        $routePath = $this->getApiRoutesPath($this->argument('name'));

        if ($this->alreadyExists($routePath)) {
            $this->error('Routes already exists!');
        } else {

            $this->makeDirectory($routePath);

            $stub = $this->files->get(base_path('resources/stubs/routes.api.stub'));

            $stub = str_replace(
                [
                    'DummyClass',
                    'DummyRoutePrefix',
                    'DummyModelVariable',
                ],
                [
                    'Api\\'.$controller.'Controller',
                    Str::plural(Str::snake(lcfirst($modelName), '-')),
                    lcfirst($modelName)
                ],
                $stub
            );

            $this->files->put($routePath, $stub);
            $this->info('Routes created successfully.');
        }


    }

    private function getApiRoutesPath(string $name) : string {

        return $this->laravel['path'] . '/Modules/'.str_replace('\\', '/', $name)."/Routes/api.php";

    }

    private function getRoutesPath(string $name) : string {

        return $this->laravel['path'].'/Modules/'.str_replace('\\', '/', $name)."/Routes/web.php";

    }

    protected function alreadyExists(string $path) : bool
    {

        return $this->files->exists($path);

    }

    private function getVueComponentPath(string $name): string
    {

        return base_path('resources/js/components/'.str_replace('\\', '/', $name).".vue");


    }

    
    private function getViewPath(string $name) : object
    {

        $arrFiles = collect([
            'create',
            'edit',
            'index',
            'show',
        ]);

        //str_replace('\\', '/', $name)
        return $arrFiles->map(function($item) use ($name){
            return base_path('resources/views/'.str_replace('\\', '/', $name).'/'.$item.".blade.php");
        });
    }


}
