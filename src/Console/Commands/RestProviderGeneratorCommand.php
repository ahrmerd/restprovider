<?php

namespace Ahmed\RestProvider\Console\Commands;

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
        $this->addOption('file', 'f', InputOption::VALUE_NONE, 'The name of the table to create');

    }

    public function handle()
    {
        if ($this->option('file')) {
            $this->importDataFromJson();
        } else {
            $this->askQuestions();
        }
        // dump($this->data);
        $modelName = $this->data['name'];
        $MigrationName = strtolower(Str::plural($modelName));
        // $this->exportDataToJson();
        // $this->createMigration($MigrationName);
        // $this->createModel($modelName);
        $this->createPolicy($modelName);
        $this->createController($modelName);
        return Command::SUCCESS;
    }

    public function askQuestions()
    {
        $name = $this->ask('What is the name of the entity');
        $modelName = Str::studly($name);

        $attributes = explode(',', $this->ask('what the attributes that describe the entity separated by a comma'));
        $this->info('your attributes are: ' . implode(' ', $attributes));
        $this->info('you will now answer questions concerning each of those attributes');

        $this->askAttributeQuestions($attributes);
        $this->info('');
        $this->data['name'] = $modelName;

        $this->info('for each of the info answer a for admin permission, u for user permisions, and g for guest permissions');
        $viewany = $this->ask('view any');
        $view = $this->ask('view');
        $create = $this->ask('create');
        $update = $this->ask('update');
        $delete = $this->ask('delete');
        $this->data['policy'] = ['viewany' => $viewany, 'view' => $view, 'create' => $create, 'update' => $update, 'delete' => $delete];

        $hasmany = explode(',', $this->ask('this resource has a oneTomany relationship with which model or resource. separate them using a string'));
        $this->data['onetomany'] = $hasmany;
        $belongsto = explode(',', $this->ask('this resource has a manyToOne relationship with which model or resource. separate them using a string'));
        $this->data['manytone'] = $belongsto;
    }

    public function askAttributeQuestions(array $attributes)
    {
        foreach ($attributes as $key => &$attribute) {
            $this->info($attribute);
            $datatype = $this->askWithCompletion('what is the data type', $this->datatypes);
            $relate = $this->askBoolean('does it refer to another table. y for yes');
            if ($relate) {
                $relate = $this->ask('which model does it relate to e.g users:id . i.e reference id on users table');
            }
            $default = $this->askBoolean('does the attribute have a default value');
            if ($default) {
                $default = $this->ask('what is the value');
            }
            $nullable = $this->askBoolean('is the attribute nullable?');
            $fillable = $this->askBoolean('is the attribute fillable?');
            $unique = $this->askBoolean('is the attribute unique?');
            $others = explode(',', $this->ask('what are some other properties? separate properties with a comma or leave blank'));
            $validation = $this->ask('what are the validation rules?, just as you will specify the in a form request e.g required,min:5,unique ');
            $sort = $this->askBoolean('is this attribute sortable');
            $filter = $this->askBoolean('is the attribute filterable?') ? ($this->askBoolean('should it be exact filtering?') ? 'exact' : 'not') : false;
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
            $attribute['other properties'] = $others;
            $attribute['validation'] = $validation;
        }
        $this->data['attributes'] = $attributes;
    }

    public function createMigration($name)
    {
        $columns = array_map(function ($attribute) {
            $nullable = ($attribute['nullable'] ? ',nullable' : '');
            $unique = ($attribute['unique'] ? ',unique,' : '');
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
        dump($columns);
        // '--column=name,datatype,attributes';
        $this->call('restprovider:migration', ['--table' => $name, '--column' => $columns]);
    }

    public function createModel($name)
    {
        $hasmany = implode(',', $this->data['hasmany']);
        $belongsto = implode(',', $this->data['belongsto']);
        $fillable = implode(',', array_map(fn($attr) => $attr['name'], array_filter($this->data['attributes'], fn($attr) => $attr['fillable'])));
        dump($hasmany);
        dump($belongsto);
        dump($fillable);
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
        dump($filters, $includes, $sorts, $updaterules, $storerules);

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
        file_put_contents(__DIR__ . '/test.json', json_encode($this->data));
    }

    public function importDataFromJson()
    {
        $this->data = json_decode(file_get_contents(__DIR__ . '/test.json'), true);
    }
    public function askBoolean(string $question, $comparVal = 'y')
    {
        return (strtolower($this->ask($question)) == $comparVal) ? true : false;
        ;
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