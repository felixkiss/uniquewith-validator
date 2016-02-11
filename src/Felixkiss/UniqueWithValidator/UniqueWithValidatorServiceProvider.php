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

        // Registering the validator extension with the validator factory
        $this->app['validator']->resolver(
            function($translator, $data, $rules, $messages, $customAttributes = array())
            {
                return new ValidatorExtension(
                    $translator,
                    $data,
                    $rules,
                    $messages,
                    $customAttributes
                );
            }
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }
}
