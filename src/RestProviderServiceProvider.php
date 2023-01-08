<?php

namespace Ahmed\RestProvider;

use Ahmed\RestProvider\Console\Commands\RestProviderControllerActions;
use Ahmed\RestProvider\Console\Commands\RestProviderControllerActionsCommand;
use Ahmed\RestProvider\Console\Commands\RestProviderControllerCommand;
use Ahmed\RestProvider\Console\Commands\RestProviderGeneratorCommand;
use Ahmed\RestProvider\Console\Commands\RestProviderMigrationCommand;
use Ahmed\RestProvider\Console\Commands\RestProviderModelCommand;
use Ahmed\RestProvider\Console\Commands\RestProviderPolicyCommand;
use Ahmed\RestProvider\Console\Commands\RestProviderRequestCommand;
use Illuminate\Support\ServiceProvider;

class RestProviderServiceProvider extends ServiceProvider {
  public function register() {
    //
  }

  public function boot() {
    $this->commands([
      RestProviderControllerActionsCommand::class,
      RestProviderControllerCommand::class,
      RestProviderGeneratorCommand::class,
      RestProviderModelCommand::class,
      RestProviderMigrationCommand::class,
      RestProviderRequestCommand::class,
      RestProviderPolicyCommand::class,
    ]);
  }
}