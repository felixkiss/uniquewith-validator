<?php namespace Felixkiss\UniqueWithValidator;

use Illuminate\Support\ServiceProvider;

class UniqueWithValidatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../../lang',
            'uniquewith-validator'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Whenever the validator factory is accessed in the container, we set
        // the custom resolver on it (this works in Larvel >= 5.2 as well).
        $this->app->resolving('validator', function ($factory, $app) {
            $factory->resolver(function ($translator, $data, $rules, $messages, $customAttributes = []) {
                return new ValidatorExtension(
                    $translator,
                    $data,
                    $rules,
                    $messages,
                    $customAttributes
                );
            });
        });
    }
}
