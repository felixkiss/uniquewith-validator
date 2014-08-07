<?php namespace Felixkiss\UniqueWithValidator;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Factory;

class UniqueWithValidatorServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('felixkiss/uniquewith-validator');

        // Registering the validator extension with the validator factory
        $this->app['validator']->resolver(function($translator, $data, $rules, $messages)
        {
            return new ValidatorExtension(
                $translator,
                $data,
                $rules,
                $messages
            );
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }
}
