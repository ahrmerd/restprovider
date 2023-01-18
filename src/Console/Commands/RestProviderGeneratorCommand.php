<?php

namespace Ahrmerd\RestProvider\Console\Commands;

use Illuminate\Console\Command;
use Str;
use Symfony\Component\Console\Input\InputOption;

class RestProviderGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $data = [];
    protected $signature = 'restprovider:generate';

    protected $datatypes = ['bool', 'smallint', 'usmallint', 'int', 'uint', 'bigint', 'ubigint', 'string'];



    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */

    protected function configure()
    {
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'file path from the laravel installation directory');
    }

    public function handle()
    {
        if ($this->option('file')) {
            if($this->importDataFromJson() == Command::FAILURE) return Command::FAILURE;
        } else {
            $this->askQuestions();
            $this->exportDataToJson();
        }
        if ( $this->option('file') || $this->askBoolean('do you want to proceed with creating a migration, model, controller, request and policy class')) {
            $modelName = $this->data['name'];
            $MigrationName = strtolower(Str::plural($modelName));
            $this->createMigration($MigrationName);
            $this->createModel($modelName);
            $this->createPolicy($modelName);
            $this->createController($modelName);
        }
        // dump($this->data);
        // $this->exportDataToJson();
        // $this->createMigration($MigrationName);
        // $this->createModel($modelName);
        // $this->createPolicy($modelName);
        // $this->createController($modelName);
        return Command::SUCCESS;
    }

    public function askQuestions()
    {
        $name = $this->ask('What is the name of the entity');
        $modelName = Str::studly($name);

        $attributes = explode(',', $this->ask('what the attributes that describe the entity separated by a comma'));
        $this->info('your attributes are: ' . implode(' ', $attributes));
        $this->info('you will now answer questions concerning each of those attributes');

        $this->askAttributeQuestions($modelName, $attributes);
        $this->info('');
        $this->data['name'] = $modelName;

        $this->info('for each of the following policy permissions, answer a for admin permission, u for user permisions, and g for guest permissions and o for owner permission');
        $viewany = $this->ask('view any or view all');
        $view = $this->ask('view one');
        $create = $this->ask('create');
        $update = $this->ask('update');
        $delete = $this->ask('delete');
        $this->data['policy'] = ['viewany' => $viewany, 'view' => $view, 'create' => $create, 'update' => $update, 'delete' => $delete];

        $hasmany = explode(',', $this->ask('this resource has a many relationship with which model or resource. separate them using a string'));
        $this->data['hasmany'] = array_filter($hasmany);
        $belongsto = explode(',', $this->ask('this resource has a belongs to relationship with which model or resource. separate them using a string'));
        $this->data['belongsto'] = array_filter($belongsto);
    }

    public function askAttributeQuestions(string $entity, array $attributes)
    {
        foreach ($attributes as $key => &$attribute) {
            $this->info($attribute);
            $datatype = $this->askWithCompletion('what is the data type of ' . $attribute, $this->datatypes);
            $relate = $this->askBoolean('does it refer to another table. y for yes');
            if ($relate) {
                $relate = $this->ask('which model does it relate to e.g users:id . i.e reference id on users table');
            }
            $default = $this->askBoolean("does $attribute have a default value");
            if ($default) {
                $default = $this->ask('what is the value');
            }
            $nullable = $this->askBoolean("is the $attribute field nullable?");
            $fillable = $this->askBoolean("is the $attribute field fillable?");
            $unique = $this->askBoolean("should $attribute have a unique database constraint?");
            $others = explode(',', $this->ask('what are some other database properties or constraints? separate properties with a comma or leave blank'));
            $sort = $this->askBoolean("can $entity be sorted by $attribute?");
            $filter = $this->askBoolean("can $entity be filtered by $attribute?") ? ($this->askBoolean('should it be exact filtering?') ? 'exact' : 'not') : false;
            $validation = $this->ask('what are the validation rules?, just as you will specify the in a form request e.g required|min:5|unique ');
            $name = $attribute;
            $attribute = [];
            $attribute['name'] = $name;
            $attribute['default'] = $default;
            $attribute['related'] = $relate;
            $attribute['datatype'] = $datatype;
            $attribute['default'] = $default;
            $attribute['unique'] = $unique;
            $attribute['nullable'] = $nullable;
            $attribute['fillable'] = $fillable;
            $attribute['sortable'] = $sort;
            $attribute['filterable'] = $filter;
            $attribute['other properties'] = array_filter($others);
            $attribute['validation'] = $validation;
        }
        $this->data['attributes'] = $attributes;
    }

    public function createMigration($name)
    {
        $columns = array_map(function ($attribute) {
            $nullable = ($attribute['nullable'] ? ',nullable' : '');
            $unique = ($attribute['unique'] ? ',unique' : '');
            $default = ($attribute['default'] ? ',default:' . $attribute['default'] : '');
            $references = ($attribute['related'] ? ',references:' . $attribute['related'] : '');
            $others = implode(',', $attribute['other properties']);
            return $attribute['name']
                . ',' . $attribute['datatype']
                . $nullable
                . $unique
                . $default
                . $references
                . $others;
        }, $this->data['attributes']);
        // '--column=name,datatype,attributes';
        $this->call('restprovider:migration', ['--table' => $name, '--column' => $columns]);
    }

    public function createModel($name)
    {
        $hasmany = implode(',', $this->data['hasmany']);
        $belongsto = implode(',', $this->data['belongsto']);
        $fillable = implode(',', array_map(fn($attr) => $attr['name'], array_filter($this->data['attributes'], fn($attr) => $attr['fillable'])));
        $this->call('restprovider:model', ['name' => $name, '--hasmany' => $hasmany, '--belongsto' => $belongsto, '--fillable' => $fillable]);

    }

    public function createPolicy($modelName)
    {
        $PolicyName = $modelName . 'Policy';
        $policy = $this->data['policy'];
        $this->call('restprovider:policy', [
            'name' => $PolicyName,
            '--model' => $modelName,
            '--viewany' => $policy['viewany'],
            '--view' => $policy['view'],
            '--update' => $policy['update'],
            '--create' => $policy['create'],
            '--delete' => $policy['delete'],
        ]);

    }

    public function createController($modelName)
    {
        $controllerName = $modelName . 'Controller';
        $filters = implode(',', array_map(function ($attr) {
            return ($attr['filterable']=='exact' ? '-': ''). $attr['name'];
        }, array_filter($this->data['attributes'], fn($attr) => $attr['filterable'])));
        $includes = implode(',' ,array_merge($this->data['hasmany'], $this->data['belongsto']));
        $sorts = implode(',', array_map(fn($attr) => $attr['name'], array_filter($this->data['attributes'], fn($attr) => $attr['sortable'])));
        $updaterules = array_map(fn($attr)=>$attr['name'].'.'.$attr['validation'],$this->data['attributes']);
        $storerules = $updaterules;

        $this->call('restprovider:controller', [
            'name' => $controllerName,
            '--model' => $modelName,
            '--querybuilder' => true,
            '--requests' => true,
            '--filters' => $filters,
            '--sorts' => $sorts,
            '--includes' => $includes,
            '--updaterules' => $updaterules,
            '--storerules' => $storerules
        ]);

    }
    public function exportDataToJson()
    {
        $filename = $this->data['name'];
        file_put_contents($this->filePath($filename), json_encode($this->data));
    }

    protected function filePath($fileName){
        $path = app_path() . "/restprovider";
        \File::ensureDirectoryExists($path);
        return $path . "/$fileName.json";
    }

    public function importDataFromJson()
    {
        
        $filepath = $this->filePath($this->option('file'));
        if(!file_exists($filepath)){
            $this->error($filepath . ' file does not exists');
            return Command::FAILURE;
        }
        $this->data = json_decode(file_get_contents($filepath), true);
    }
    public function askBoolean(string $question)
    {
        return (strtolower(in_array(strtolower(trim($this->ask($question))), ['y', 'yes']))) ? true : false;;
    }

// protected function qualifyClass($name)
// {
//     $name = ltrim($name, '\\/');

//     $name = str_replace('/', '\\', $name);

//     $rootNamespace = $this->rootNamespace();

//     if (Str::startsWith($name, $rootNamespace)) {
//         return $name;
//     }

//     return $this->qualifyClass(trim($rootNamespace, '\\')).'\\'.$name;
// }
// protected function rootNamespace()
// {
//     return $this->laravel->getNamespace();
// }
}