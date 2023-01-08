<?php

namespace Ahmed\RestProvider\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class RestProviderRequestCommand extends GeneratorCommand
{
    protected $name = 'restprovider:request';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'restprovider:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new form request class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Request';

    protected function buildClass($name)
    {
        
        
        $str = '';
        $rules = $this->option('rules');
        foreach ($rules as $rule) {
            [$attribute, $args] = explode('.', $rule);
            $str.="\t \t \t'$attribute' => ['".  $args   . "'],". PHP_EOL;
        }
        // return parent::buildClass($name);
        return str_replace(
            ['{{ rules }}', '{{rules}}'],
            $str,
            parent::buildClass($name)
        );
    }

    protected function getStub()
    {
        return __DIR__ . '/../stubs/request.stub';

    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Requests';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['rules', 'r', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'specify the rules for the field'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the request already exists'],
        ];
    }
// protected function getOptions()
// {
//     return [
//         [
//             'rule',
//             'r', InputOption::VALUE_IS_ARRAY,
//             'specify the rules for the field',],
//         [
//             'force',
//             'f', InputOption::VALUE_NONE,
//             'Create the class even if the request already exists',
//         ],
//     ];
// }
}