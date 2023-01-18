<?php

namespace Ahrmerd\RestProvider\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Database\Console\Migrations\TableGuesser;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RestProviderMigrationCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    public function __construct(protected Filesystem $files, protected Composer $composer)
    {
        parent::__construct();
    }
    protected $signature = 'restprovider:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the table to create');
        $this->addOption('table', 't', InputOption::VALUE_REQUIRED, 'The name of the table to create');
        $this->addOption('column', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'define a column');

    }
    public function handle()
    {
        $table = $this->input->getOption('table');
        $name = $this->argument('name');
        if (!$name) $name = 'create_' . $table . '_table';

        $file = $this->create(
            $name,
            $table
        );
        $this->components->info(sprintf('Migration [%s] created successfully.', $file));

        $this->composer->dumpAutoloads();

    }




    public function create($name, $table = null, $create = false)
    {
        $path = $this->getMigrationPath();

        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        $stub = $this->getStub();

        $path = $this->getPath($name, $path);

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put(
            $path, $this->populateStub($stub, $table)
        );


        return $path;
    }

    protected function ensureMigrationDoesntAlreadyExist($name, $migrationPath = null)
    {
        if (!empty($migrationPath)) {
            $migrationFiles = $this->files->glob($migrationPath . '/*.php');

            foreach ($migrationFiles as $migrationFile) {
                $this->files->requireOnce($migrationFile);
            }
        }

        if (class_exists($className = $this->getClassName($name))) {
            throw new \InvalidArgumentException("A {$className} class already exists.");
        }
    }
    protected function getClassName($name)
    {
        return \Str::studly($name);
    }



    protected function getStub()
    {

        return $this->files->get(__DIR__ . '/../stubs/migration.stub');
    }

    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }


    protected function getPath($name, $path)
    {
        return $path . '/' . $this->getDatePrefix() . '_' . $name . '.php';
    }

    protected function populateStub($stub, $table)
    {
        if (!is_null($table)) {
            $columns = $this->prepareColumns();
            $replace = [
                '{{ table }}' => $table,
                '{{table}}' => $table,
                '{{columns}}' => $columns,
                '{{ columns }}' => $columns,

            ];
            $stub = str_replace(
                array_keys($replace),
                array_values($replace),
                $stub
            );
        }

        return $stub;
    }

    protected function prepareColumns()
    {
        $columns = $this->input->getOption('column');
        $columnString = '';
        foreach ($columns as $column) {
            $columnString .= $this->prepareColumn($column);
        }

        return $columnString;
    }
    protected function prepareColumn($column)
    {
        $attributeCount = 2;
        $attributes = explode(',', $column);
        $columnText = "\t \t \t";
        if (count($attributes) >= $attributeCount) {
            $name = array_shift($attributes);
            $datatype = array_shift($attributes);
            $columnText .= '$table->';
            // [$name, $datatype] = $attrbutes;
            $columnText .= $this->prepareColumnDataType($datatype) . "('$name')";
            foreach ($attributes as $attribute) {
                if (strtolower($attribute) == 'unique') {
                    $columnText .= '->unique()';
                }
                else if (strtolower($attribute) == 'nullable') {
                    $columnText .= '->nullable()';
                }
                
                else if (str_contains(strtolower($attribute), 'default')) {
                    [, $defaultValue] = explode(':', $attribute);
                    $columnText .= "->default('$defaultValue')";
                }
                else if (str_contains(strtolower($attribute), 'references')) {
                    [, $column, $table] = explode(':', $attribute);
                    $columnText .= "->references('$column')->on('$table')->cascadeOnDelete()->cascadeOnUpdate()";
                }else{
                    $columnText .= "->$attribute()";
                }
                
            }
            $columnText .= ';' . PHP_EOL;
        }
        return $columnText;
    }

    public function prepareColumnDataType($datatype)
    {
        switch (strtolower($datatype)) {
            case 'string':
                return "string";
            case 'smallint':
                return "smallInteger";
            case 'bigint':
                return "bigInteger";
            case 'int':
                return "integer";
            case 'decimal':
                return "decimal";
            case 'boolean':
                return "boolean";
            case 'date':
                return "decimal";
            case 'datetime':
                return "datetime";
            case 'timestamp':
                return "timestamp";
            case 'foreignid':
                return "foreignId";
            default:
                return $datatype;
        }
    }
}