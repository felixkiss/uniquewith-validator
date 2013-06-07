# unique_with Validator Rule For Laravel 4

This package contains a variant of the `validateUnique` rule for Laravel 4, that allow for validation of multi-column UNIQUE indexes.

## Installation

Install the package through [Composer](http://getcomposer.org).

In your `composer.json` file:

```json
{
	"require": {
		"laravel/framework": "4.0.*",
		// ...
		"felixkiss/uniquewith-validator"
	}
}
```

Run `composer install` or `composer update` to install the package.

Add the following to your `providers` array in `config/app.php`:

```php
	'providers' => array(
		// ...

		'Felixkiss\UniqueWithValidator\UniqueWithValidatorServiceProvider',
	),
```

## Usage

Use it like any `Validator` rule:

```php
$rules = array(
	'<field1>' => 'unique_with:<table>,<field2>[,<field3>...]',
);
```

See the [Validation documentation](http://laravel.com/docs/validation) of Laravel 4.

