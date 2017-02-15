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

        $message = $this->app->translator->get('uniquewith-validator::validation.unique_with');
        $this->app->validator->extend('unique_with', Validator::class . '@validateUniqueWith', $message);
        $this->app->validator->replacer('unique_with', function() {
            $translator = $this->app->translator;
            $validator = $this->app->make(Validator::class);
            return call_user_func_array([$validator, 'replaceUniqueWith'], array_merge(func_get_args(), [$translator]));
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
