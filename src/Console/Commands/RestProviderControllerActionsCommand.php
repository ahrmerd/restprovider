<?php

namespace Ahmed\RestProvider\Console\Commands;

use Illuminate\Console\Command;

class RestProviderControllerActionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restprovider:controller-actions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the rest provider controller actions if it dosen\'t exist';



    protected function getStub() {
        return __DIR__ . '/../stubs/controller-actions.stub';
	}

    protected function getFullFilePath()
    {
        return app()->basePath() . "/app/Http/Actions/";
    }

    public function makeDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    

    protected function getFullFileName()
    {
        return $this->getFullFilePath() . 'RestProviderControllerActions.php';
    }

    public function handle()
    {
        if (file_exists($this->getFullFileName())) {
            $this->info('the actions class already exist');
        } else if (file_exists($this->getStub())) {
            $this->makeDir($this->getFullFilePath());
            // dump($this->getStub(), $this->getFullFileName());
            if (copy($this->getStub(), $this->getFullFileName())) {

                $this->info('the restprovider action was copied successfully');
            } else {
                $this->error('failed to create action class');
            }
        } else {
            $this->error('something is wrong the stub file does not exit in' . $this->getStub());
        }
        return 0;
    }
}