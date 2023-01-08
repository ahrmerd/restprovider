<?php

namespace Ahmed\RestProvider\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RestProviderModel extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'restprovider:model';


    protected static $defaultName = 'restprovider:model';

     protected $type = 'Model';
    protected $description = 'Command description';

    protected function buildClass($name)
    {
        $fillable = explode(',', $this->option('fillable'));
        $replace = [
            '{{ fillable }}'=> '['. implode(',', array_map(fn($str)=>"'$str'", $fillable)) . ']',
            '{{ hasMany }}'=> $this->generateHasManyStr(),
            '{{ belongsTo }}'=> $this->generateBelongsToStr(),
            // '_filters' => $filters,
            // '_sorts' => $sorts,
            // '_includes' => $includes,
            // '_model' => $model,
            // '_namespacemodel' => $namespaceModel,

        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }
	/**
	 * Get the stub file for the generator.
	 * @return string
	 */
	protected function getStub() {
        return __DIR__ . '/../stubs/model.stub';
	}


    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the command'],
        ];
    }

    protected function getOptions()
    {
        return [
            ['fillable', 'f', InputOption::VALUE_REQUIRED, 'The attributes that are mass assignable.'],
            ['hasmany', 'm', InputOption::VALUE_REQUIRED, 'list has many relationship'],
            ['belongsto', 'o', InputOption::VALUE_REQUIRED, 'list belongs to relationship'],
        ];
    }

    public function generateHasManyStr(){
        $models = $this->option('hasmany');
        $models = $models==null ? []: explode(',', $models);
        $code = "";
     
        foreach ($models as $model) {
            $model =  \Str::singular(strtolower($model)) ;
            $pluralForm = \Str::plural($model);
            $modelName = \Str::studly($model);
            $code .= "\t public function $pluralForm(){" . PHP_EOL. "\t \t return \$this->hasMany($modelName::class);". PHP_EOL."\t}". PHP_EOL;
        }
        return $code;

    }

    public function generateBelongsToStr(){
        $models = $this->option('belongsto');
        $models = $models==null ? []: explode(',', $models);
        $code = "";
        foreach ($models as $model) {
            $model = strtolower($model);
            $modelName = \Str::studly($model);
            $code .= "\t public function $model(){" . PHP_EOL. "\t \t return \$this->belongsTo($modelName::class);". PHP_EOL."\t}". PHP_EOL;
        }
        return $code;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return is_dir(app_path('Models')) ? $rootNamespace.'\\Models' : $rootNamespace;
    }

}
