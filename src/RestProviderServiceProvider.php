<?php

namespace Ahrmerd\RestProvider;

// use Ahmed\RestProvider\Console\Commands\RestProviderControllerActions;
use Ahrmerd\RestProvider\Console\Commands\RestProviderControllerActionsCommand;
use Ahrmerd\RestProvider\Console\Commands\RestProviderControllerCommand;
use Ahrmerd\RestProvider\Console\Commands\RestProviderGeneratorCommand;
use Ahrmerd\RestProvider\Console\Commands\RestProviderMigrationCommand;
use Ahrmerd\RestProvider\Console\Commands\RestProviderModelCommand;
use Ahrmerd\RestProvider\Console\Commands\RestProviderPolicyCommand;
use Ahrmerd\RestProvider\Console\Commands\RestProviderRequestCommand;
use Illuminate\Support\ServiceProvider;

class RestProviderServiceProvider extends ServiceProvider {
  public function register() {
    //
  }

  public function boot() {
    $this->commands([
      // RestProviderControllerActionsCommand::class,
      RestProviderControllerCommand::class,
      RestProviderGeneratorCommand::class,
      RestProviderModelCommand::class,
      RestProviderMigrationCommand::class,
      RestProviderRequestCommand::class,
      RestProviderPolicyCommand::class,
    ]);
  }
}