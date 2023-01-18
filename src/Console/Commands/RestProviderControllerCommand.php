<?php

namespace Ahrmerd\RestProvider\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class RestProviderControllerCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'restprovider:controller';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'restprovider:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('querybuilder')) {
            return __DIR__ . '/../stubs/controller.provider.stub';
        }
        return __DIR__ . '/../stubs/controller.stub';

    }


    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Controllers';
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in the base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $controllerNamespace = $this->getNamespace($name);

        $replace = [];


        $replace = $this->buildModelReplacements($replace);



        $replace["use {$controllerNamespace}\Controller;\n"] = '';

        return str_replace(
            array_keys($replace),
            array_values($replace), parent::buildClass($name)
        );
    }

    // protected function buildIndexResponseReplacement(string $stub)
    // {
    //     if ($this->option('querybuilder')) {
    //         $this->generateActionFunctions($stub);
    //     }
    // }

    // protected function generateActionClass()
    // {
    //     if (file_exists(app()->basePath() . "/app/Http/Actions/RestProviderControllerActions.php")) return;
    //     $this->call('restprovider:controller-actions',);
    // }


    /**
     * Build the model replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildModelReplacements(array $replace)
    {
        $modelClass = $this->parseModel($this->option('model'));

        // if (!class_exists($modelClass) && $this->components->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true)) {
        //     $this->call('make:model', ['name' => $modelClass]);
        // }


        $replace = $this->buildFormRequestReplacements($replace, $modelClass);

        
        $replace = $this->buildIncludesFiltersSortsReplacements($replace);

        return array_merge($replace, [
            'DummyFullModelClass' => $modelClass,
            '{{ namespacedModel }}' => $modelClass,
            '{{namespacedModel}}' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            '{{ model }}' => class_basename($modelClass),
            '{{model}}' => class_basename($modelClass),
            '{{modellower}}' => strtolower(class_basename($modelClass)),
            '{{ modellower }}' => strtolower(class_basename($modelClass)),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
            '{{ modelVariable }}' => lcfirst(class_basename($modelClass)),
            '{{modelVariable}}' => lcfirst(class_basename($modelClass)),
        ]);
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param  string  $model
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new \InvalidArgumentException('Model name contains invalid characters.');
        }

        return $this->qualifyModel($model);
    }

    protected function buildIncludesFiltersSortsReplacements(array $replace){
        
        $filters = '[]';
        $sorts = '[]';
        $includes = '[]';
        if ($this->option('filters') && $this->option('filters')!=null) {
            $filters = array_map(function ($filter) {
                if(str_starts_with($filter, '-')){
                    return "AllowedFilter::exact('" . ltrim($filter, '-')."')";
                }
                return "'" . $filter . "'";
            },explode(',', $this->option('filters')));
            $filters = '['. implode(',', $filters) .']';
        }
        if ($this->option('sorts')) {
            $sorts = array_map(fn($sort)=>"'" . $sort. "'" ,explode(',', $this->option('sorts')));
            $sorts = '['.implode(',', $sorts) . ']';
        }
        if ($this->option('includes')) {
            $includes = array_map(fn($includes)=>"'" . $includes. "'" ,explode(',', $this->option('includes')));
            $includes = '['. implode(',', $includes) . ']';
        }
        return array_merge($replace, [
            '{{ filters }}' => $filters,
            '{{filters}}' => $filters,
            '{{ includes }}' => $includes,
            '{{includes}}' => $includes,
            '{{ sorts }}' => $sorts,
            '{{sorts}}' => $sorts

        ]);
    }
        protected function buildFormRequestReplacements(array $replace, $modelClass)
    {
        [$namespace, $storeRequestClass, $updateRequestClass] = [
            'Illuminate\\Http',
            'Request',
            'Request',
        ];

        if ($this->option('requests')) {
            $namespace = 'App\\Http\\Requests';

            [$storeRequestClass, $updateRequestClass] = $this->generateFormRequests(
                $modelClass,
                $storeRequestClass,
                $updateRequestClass
            );
        }

        $namespacedRequests = $namespace . '\\' . $storeRequestClass . ';';

        if ($storeRequestClass !== $updateRequestClass) {
            $namespacedRequests .= PHP_EOL . 'use ' . $namespace . '\\' . $updateRequestClass . ';';
        }

        return array_merge($replace, [
            '{{ storeRequest }}' => $storeRequestClass,
            '{{storeRequest}}' => $storeRequestClass,
            '{{ updateRequest }}' => $updateRequestClass,
            '{{updateRequest}}' => $updateRequestClass,
            '{{ namespacedStoreRequest }}' => $namespace . '\\' . $storeRequestClass,
            '{{namespacedStoreRequest}}' => $namespace . '\\' . $storeRequestClass,
            '{{ namespacedUpdateRequest }}' => $namespace . '\\' . $updateRequestClass,
            '{{namespacedUpdateRequest}}' => $namespace . '\\' . $updateRequestClass,
            '{{ namespacedRequests }}' => $namespacedRequests,
            '{{namespacedRequests}}' => $namespacedRequests,
        ]);
    }


    /**
     * Build the model replacement values.
     *
     * @param  array  $replace
     * @param  string  $modelClass
     * @return array
     */

    protected function generateFormRequests($modelClass, $storeRequestClass, $updateRequestClass)
    {
        $storeRequestClass = 'Store' . class_basename($modelClass) . 'Request';

        $this->call('restprovider:request', [
            'name' => $storeRequestClass,
            '--rules' => $this->option('storerules')
        ]);

        $updateRequestClass = 'Update' . class_basename($modelClass) . 'Request';
        
        $this->call('restprovider:request', [
            'name' => $updateRequestClass,
            '--rules' => $this->option('updaterules')

        ]);

        return [$storeRequestClass, $updateRequestClass];
    }
    protected function getOptions()
    {
        return [
            ['api', null, InputOption::VALUE_NONE, 'Exclude the create and edit methods from the controller'],
            ['querybuilder', 'Q', InputOption::VALUE_NONE, 'create controller with query builder actions'],
            ['type', null, InputOption::VALUE_REQUIRED, 'Manually specify the controller stub file to use'],
            ['filters', null, InputOption::VALUE_REQUIRED, 'Manually specify the controller stub file to use'],
            ['includes', null, InputOption::VALUE_REQUIRED, 'Manually specify the controller stub file to use'],
            ['sorts', null, InputOption::VALUE_REQUIRED, 'Manually specify the controller stub file to use'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the controller already exists'],
            ['invokable', 'i', InputOption::VALUE_NONE, 'Generate a single method, invokable controller class'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given model'],
            ['storerules', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'specify the rules for the store request'],
            ['updaterules', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'specify the rules for the update'],
            ['requests', 'R', InputOption::VALUE_NONE, 'Generate FormRequest classes for store and update'],
            ['singleton', 's', InputOption::VALUE_NONE, 'Generate a singleton resource controller class'],
            ['creatable', null, InputOption::VALUE_NONE, 'Indicate that a singleton resource should be creatable'],
        ];
    }
}