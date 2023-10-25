<?php

namespace Saade\Cep;

use Saade\Cep\Commands\CepCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CepServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('cep-php')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_cep-php_table')
            ->hasCommand(CepCommand::class);
    }
}
