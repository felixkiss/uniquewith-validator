<?php namespace Felixkiss\UniqueWithValidator;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
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

        if (method_exists($this->app->translator, 'trans')) {
            $message = $this->app->translator->trans('uniquewith-validator::validation.unique_with');
        }
        else {
            $message = $this->app->translator->get('uniquewith-validator::validation.unique_with');
        }
        $this->app->validator->extend('unique_with', Validator::class . '@validateUniqueWith', $message);
        $this->app->validator->replacer('unique_with', function() {
            // Since 5.4.20, the validator is passed in as the 5th parameter.
            // In order to preserve backwards compatibility, we check if the 
            // validator is passed and use the validator's translator instead
            // of getting it out of the container.
            $arguments = func_get_args();
            if (sizeof($arguments) >= 5) {
                $arguments[4] = $arguments[4]->getTranslator();
            }
            else {
                $arguments[4] = $this->app->translator;
            }

            return call_user_func_array([new Validator, 'replaceUniqueWith'], $arguments);
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
}
