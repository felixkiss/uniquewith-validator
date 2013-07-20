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
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('felixkiss/uniquewith-validator');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Registering the validator extension
		$this->app['validator']->resolver(function($translator, $data, $rules, $messages)
		{
			// Set custom validation error messages
			$messages['unique_with'] = $translator->get('uniquewith-validator::validation.unique_with');

			return new ValidatorExtension($translator, $data, $rules, $messages);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('validator');
	}
}