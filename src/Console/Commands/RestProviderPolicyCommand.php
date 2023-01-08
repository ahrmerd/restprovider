<?php

namespace Ahmed\RestProvider\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use App\Models\User;
use Str;
use Symfony\Component\Console\Input\InputOption;

class RestProviderPolicyCommand extends GeneratorCommand
{

    protected $name = 'restprovider:policy';
    protected static $defaultName = 'restprovider:policy';
    protected $description = 'Create a new policy class';

    protected $type = 'Policy';

    protected function buildClass($name)
    {

        $stub = $this->replaceUserNamespace(
            parent::buildClass($name)
        );
        
        $model = $this->option('model');

        return $this->replaceModel($stub, $model);
    }

    protected function replaceUserNamespace(string $stub)
    {
        $model = $this->userProviderModel();

        if (!$model) {
            return $stub;
        }

        return str_replace(
            $this->rootNamespace() . 'User',
            $model,
            $stub
        );
    }

    protected function userProviderModel()
    {
        $config = $this->laravel['config'];

        $guard = $this->option('guard') ?: $config->get('auth.defaults.guard');

        if (is_null($guardProvider = $config->get('auth.guards.' . $guard . '.provider'))) {
            throw new \LogicException('The [' . $guard . '] guard is not defined in your "auth" configuration file.');
        }

        if (!$config->get('auth.providers.' . $guardProvider . '.model')) {
            return 'App\\Models\\User';
        }

        return $config->get(
            'auth.providers.' . $guardProvider . '.model'
        );
    }

    protected function replaceModel($stub, $model)
    {
        $model = str_replace('/', '\\', $model);

        if (str_starts_with($model, '\\')) {
            $namespacedModel = trim($model, '\\');
        } else {
            $namespacedModel = $this->qualifyModel($model);
        }

        $model = class_basename(trim($model, '\\'));

        $dummyUser = class_basename($this->userProviderModel());

        $dummyModel = Str::camel($model) === 'user' ? 'model' : $model;

        $replace = [
            'NamespacedDummyModel' => $namespacedModel,
            '{{ namespacedModel }}' => $namespacedModel,
            '{{namespacedModel}}' => $namespacedModel,
            'DummyModel' => $model,
            '{{ model }}' => $model,
            '{{model}}' => $model,
            'dummyModel' => Str::camel($dummyModel),
            '{{ modelVariable }}' => Str::camel($dummyModel),
            '{{modelVariable}}' => Str::camel($dummyModel),
            'DummyUser' => $dummyUser,
            '{{ user }}' => $dummyUser,
            '{{user}}' => $dummyUser,
            '$user' => '$' . Str::camel($dummyUser),
        ];

        $stub = str_replace(
            array_keys($replace),
            array_values($replace),
            $stub
        );

        $substub = preg_replace(
            vsprintf('/use %s;[\r\n]+use %s;/', [
                preg_quote($namespacedModel, '/'),
                preg_quote($namespacedModel, '/'),
            ]),
            "use {$namespacedModel};",
            $stub
        );
        return $this->setPolicyPermissions($substub, Str::camel($dummyModel));

    }

    protected function getStub() {
        return __DIR__ . '/../stubs/policy.stub';
	}

    // protected function getStub()
    // {
    //     return $this->option('model')
    //         ? $this->resolveStubPath('/stubs/policy.stub')
    //         : $this->resolveStubPath('/stubs/policy.plain.stub');
    // }

    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }
    // protected function getDefaultNamespace($rootNamespace)
    // {
    //     return $rootNamespace . '\Policies';
    // }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Policies';
    }

    protected function resolvePermissions($permission, $model)
    {
        switch ($permission) {
            case 'a':
            case 'admin':
                return $this->adminPermission();
            case 'o':
            case 'owner':
                return $this->ownerPermission($model);
            case 'u':
            case 'user':
                return $this->userPermission();
            case 'g':
            case 'guest':
                return $this->guestPermission();
            default:
                return 'false';
            
        }
    }
    protected function adminPermission()
    {
        if(method_exists('App\\Models\\User', 'isAdmin')){
            return '(!!$user?->isAdmin())';
        }
        if(property_exists('App\\Models\\User', 'is_admin')){
            return '(!!$user?->is_admin)';
        }
        return $this->userPermission();
    }
    protected function userPermission()
    {
        return '(!!$user)';
    }

    protected function ownerPermission($model)
    {
        return '$user->id == $'. $model . '->id';
    }
    protected function guestPermission()
    {
        return 'true';
    }

    protected function setPolicyPermissions(string $stub, $model)
    {
        $replace = [
            '{{ viewAny }}' => $this->resolvePermissions($this->option('viewany'), $model),
            '{{ view }}' => $this->resolvePermissions($this->option('view'), $model),
            '{{ create }}' => $this->resolvePermissions($this->option('create'), $model),
            '{{ update }}' => $this->resolvePermissions($this->option('update'), $model),
            '{{ delete }}' => $this->resolvePermissions($this->option('delete'), $model),

        ];

        $stub = str_replace(
            array_keys($replace),
            array_values($replace),
            $stub
        );
        return $stub;
    }


    protected function getOptions()
    {
        return [
            ['viewany', 'a', InputOption::VALUE_REQUIRED, 'Determine which user can view any models'],
            ['view', 'o', InputOption::VALUE_REQUIRED, 'Determine which user can view the models'],
            ['create', 'c', InputOption::VALUE_REQUIRED, 'Determine which user can create the model'],
            ['update', 'u', InputOption::VALUE_REQUIRED, 'Determine which user can update the model'],
            ['delete', 'd', InputOption::VALUE_REQUIRED, 'Determine which user can delete the model'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the policy already exists'],
            ['model', 'm', InputOption::VALUE_REQUIRED, 'The model that the policy applies to'],
            ['guard', 'g', InputOption::VALUE_OPTIONAL, 'The guard that the policy relies on'],
        ];
    }
}