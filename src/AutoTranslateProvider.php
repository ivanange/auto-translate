<?php

namespace Devaslanphp\AutoTranslate;

use Devaslanphp\AutoTranslate\Commands\AutoTranslate;
use Devaslanphp\AutoTranslate\Commands\TranslateMissing;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AutoTranslateProvider extends PackageServiceProvider {

    /**
     * Configure the package.
     *
     * @return void
     */
    public function configurePackage(Package $package): void {
        $package
            ->name('auto-translate')
            ->hasConfigFile()
            ->hasCommands([
                AutoTranslate::class,
                TranslateMissing::class
            ]);
    }

    /**
     * After package is registered.
     *
     * @return void
     */
    public function packageRegistered() {
        $this->app->singleton(Translator::class, function ($app) {
            $loader = app('translation.loader');

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app->getLocale();

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app->getFallbackLocale());

            return $trans;
        });
    }
}
