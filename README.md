# unique_with Validator Rule For Laravel

[![Join the chat at https://gitter.im/felixkiss/uniquewith-validator](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/felixkiss/uniquewith-validator?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![Build Status](https://travis-ci.org/felixkiss/uniquewith-validator.png?branch=master)](https://travis-ci.org/felixkiss/uniquewith-validator)

This package contains a variant of the `validateUnique` rule for Laravel, that allows for validation of multi-column UNIQUE indexes.

## Installation

Install the package through [Composer](http://getcomposer.org).

### Laravel 4

On the command line:

```
composer require felixkiss/uniquewith-validator:1.*
```

### Laravel 5

On the command line:

```
composer require felixkiss/uniquewith-validator:2.*
```

## Configuration

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
    '<field1>' => 'unique_with:<table>,<field2>[,<field3>,...,<ignore_rowid>]',
);
```

See the [Validation documentation](http://laravel.com/docs/validation) of Laravel.

### Specify different column names in the database

If your input field names are different from the corresponding database columns,
you can specify the column names explicitly.

e.g. your input contains a field 'last_name', but the column in your database is called 'sur_name':
```php
$rules = array(
    'first_name' => 'unique_with:users, middle_name, last_name = sur_name',
);
```

## Example

Pretend you have a `users` table in your database plus `User` model like this:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function(Blueprint $table) {
            $table->increments('id');

            $table->timestamps();

            $table->string('first_name');
            $table->string('last_name');

            $table->unique(array('first_name', 'last_name'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }

}
```

```php
<?php

class User extends Eloquent { }
```

Now you can validate a given `first_name`, `last_name` combination with something like this:

```php
Route::post('test', function() {
    $rules = array(
        'first_name' => 'required|unique_with:users,last_name',
        'last_name' => 'required',
    );

    $validator = Validator::make(Input::all(), $rules);

    if($validator->fails())
    {
        return Redirect::back()->withErrors($validator);
    }

    $user = new User;
    $user->first_name = Input::get('first_name');
    $user->last_name = Input::get('last_name');
    $user->save();

    return Redirect::home()->with('success', 'User created!');
});
```

You can also specify a row id to ignore (useful to solve unique constraint when updating)

This will ignore row with id 2

```php
$rules = array(
    'first_name' => 'required|unique_with:users,last_name,2',
    'last_name' => 'required',
);
```

To specify a custom column name for the id, pass it like

```php
$rules = array(
    'first_name' => 'required|unique_with:users,last_name,2 = custom_id_column',
    'last_name' => 'required',
);
```

You can also set additional clauses. For example, if your model uses soft deleting
then you can use the following code to select all existing rows but marked as deleted

```php
$rules = array(
    'first_name' => 'required|unique_with:users,last_name,deleted_at,2 = custom_id_column',
    'last_name' => 'required',
);
```

## Extending the Laravel Validator

For simple validation rules that don't need translator or custom messages:
```php
Validator::extend('foo_bar', function($attribute, $value, $parameters)
{
    return ($attribute == 'foo' && $value == 'bar');
});
```

For more sophisticated rules:
```php
class CustomValidator extends Felixkiss\UniqueWithValidator\ValidatorExtension
{
    public function validateOnlyApple($attribute, $value, $parameters)
    {
        // $this->translator, $this->messages, etc. available
        return ($attribute == 'company' && $value == 'apple');
    }
}

Validator::resolver(function($translator, $data, $rules, $messages)
{
    return new CustomValidator($translator, $data, $rules, $messages);
});
```

# License

MIT
