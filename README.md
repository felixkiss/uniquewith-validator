# unique_with Validator Rule For Laravel

[![Build Status](https://travis-ci.org/felixkiss/uniquewith-validator.svg?branch=master)](https://travis-ci.org/felixkiss/uniquewith-validator)

This package contains a variant of the `validateUnique` rule for Laravel, that allows for validation of multi-column UNIQUE indexes.

## Documentation for older versions

 - [Laravel 4](https://github.com/felixkiss/uniquewith-validator/blob/2.0.8/README.md#laravel-4)

## Installation

Install the package through [Composer](http://getcomposer.org).
On the command line:

```
composer require felixkiss/uniquewith-validator
```

## Configuration

Add the following to your `providers` array in `config/app.php`:

```php
'providers' => [
    // ...

    Felixkiss\UniqueWithValidator\ServiceProvider::class,
],
```

## Usage

Use it like any `Validator` rule:

```php
$rules = [
    '<field1>' => 'unique_with:<table>,<field2>[,<field3>,...,<ignore_rowid>]',
];
```

See the [Validation documentation](http://laravel.com/docs/validation) of Laravel.

### Specify different column names in the database

If your input field names are different from the corresponding database columns,
you can specify the column names explicitly.

e.g. your input contains a field 'last_name', but the column in your database is called 'sur_name':
```php
$rules = [
    'first_name' => 'unique_with:users, middle_name, last_name = sur_name',
];
```

### Ignore existing row (useful when updating)

You can also specify a row id to ignore (useful to solve unique constraint when updating)

This will ignore row with id 2

```php
$rules = [
    'first_name' => 'required|unique_with:users,last_name,2',
    'last_name' => 'required',
];
```

To specify a custom column name for the id, pass it like

```php
$rules = [
    'first_name' => 'required|unique_with:users,last_name,2 = custom_id_column',
    'last_name' => 'required',
];
```

If your id is not numeric, you can tell the validator

```php
$rules = [
    'first_name' => 'required|unique_with:users,last_name,ignore:abc123',
    'last_name' => 'required',
];
```

### Add additional clauses (e.g. when using soft deletes)

You can also set additional clauses. For example, if your model uses soft deleting
then you can use the following code to select all existing rows but marked as deleted

```php
$rules = [
    'first_name' => 'required|unique_with:users,last_name,deleted_at,2 = custom_id_column',
    'last_name' => 'required',
];
```

*Soft delete caveat:*

In Laravel 5 (tested on 5.5), if the validation is performed in form request class, field deleted_at is skipped, because it's not send in request. To solve this problem, add 'deleted_at' => null to Your validation parameters in request class., e.g.:

```php
protected function validationData()
{
    return array_merge($this->request->all(), [
        'deleted_at' => null
    ]);
}
```

### Specify specific database connection to use

If we have a connection named `some-database`, we can enforce this connection (rather than the default) like this:

```php
$rules = [
    'first_name' => 'unique_with:some-database.users, middle_name, last_name',
];
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

            $table->unique(['first_name', 'last_name']);
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
    $rules = [
        'first_name' => 'required|unique_with:users,last_name',
        'last_name' => 'required',
    ];

    $validator = Validator::make(Input::all(), $rules);

    if($validator->fails()) {
        return Redirect::back()->withErrors($validator);
    }

    $user = new User;
    $user->first_name = Input::get('first_name');
    $user->last_name = Input::get('last_name');
    $user->save();

    return Redirect::home()->with('success', 'User created!');
});
```

# License

MIT
