<?php namespace Felixkiss\UniqueWithValidator;

use Illuminate\Validation\Validator;

class ValidatorExtension extends Validator
{
    /**
     * Creates a new instance of ValidatorExtension
     */
    public function __construct($translator, $data, $rules, $messages, array $customAttributes = array())
    {
        // Set custom validation error messages
        if(!isset($messages['unique_with']))
        {
            $messages['unique_with'] = $translator->get(
                'uniquewith-validator::validation.unique_with'
            );
        }

        parent::__construct($translator, $data, $rules, $messages, $customAttributes);
    }

    /**
     * Usage: unique_with: table, column1, column2, ...
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @param  array $parameters
     * @return boolean
     */
    public function validateUniqueWith($attribute, $value, $parameters)
    {
        // cleaning: trim whitespace
        $parameters = array_map('trim', $parameters);

        // first item equals table name
        $table = array_shift($parameters);

        // The second parameter position holds the name of the column that
        // needs to be verified as unique. If this parameter isn't specified
        // we will just assume that this column to be verified shares the
        // attribute's name.
        $column = $attribute;

        // Create $extra array with all other columns, so getCount() will
        // include them as where clauses as well
        $extra = array();

        // Check if last parameter is an integer. If it is, then it will
        // ignore the row with the specified id - useful when updating a row
        list($ignore_id, $ignore_column) = $this->getIgnore($parameters);

        // Figure out whether field_name is the same as column_name
        // or column_name is explicitly specified.
        //
        // case 1:
        //     $parameter = 'last_name'
        //     => field_name = column_name = 'last_name'
        // case 2:
        //     $parameter = 'last_name=sur_name'
        //     => field_name = 'last_name', column_name = 'sur_name'
        foreach ($parameters as $parameter)
        {
            $parameter = array_map('trim', explode('=', $parameter, 2));
            $field_name = $parameter[0];

            if (count($parameter) > 1)
            {
                $column_name = $parameter[1];
            }
            else
            {
                $column_name = $field_name;
            }

            // Figure out whether main field_name has an explicitly specified
            // column_name
            if ($field_name == $column)
            {
                $column = $column_name;
            }
            else
            {
                $extra[$column_name] = array_get($this->data, $field_name);
            }
        }

        // The presence verifier is responsible for counting rows within this
        // store mechanism which might be a relational database or any other
        // permanent data store like Redis, etc. We will use it to determine
        // uniqueness.
        $verifier = $this->getPresenceVerifier();

        return $verifier->getCount(
            $table,
            $column,
            $value,
            $ignore_id,
            $ignore_column,
            $extra
        ) == 0;
    }

    public function replaceUniqueWith($message, $attribute, $rule, $parameters)
    {
        // remove trailing ID param if present
        $this->getIgnore($parameters);

        // merge primary field with conditional fields
        $fields = array($attribute) + $parameters;

        // get full language support due to mapping to validator getAttribute
        // function
        $fields = array_map(array($this, 'getAttribute'), $fields);

        // fields to string
        $fields = implode(', ', $fields);

        return str_replace(':fields', $fields, $message);
    }

    /**
     * Returns an array with value and column name for an optional ignore.
     * Shaves of the ignore_id from the end of the array, if there is one.
     *
     * @param  array $parameters
     * @return array [$ignoreId, $ignoreColumn]
     */
    private function getIgnore(&$parameters)
    {
        $lastParam = end($parameters);
        $lastParam = array_map('trim', explode('=', $lastParam));

        // An ignore_id is only specified if the last param starts with a
        // number greater than 1 (a valid id in the database)
        if (!preg_match('/^[1-9][0-9]*$/', $lastParam[0]))
        {
            return array(null, null);
        }

        $ignoreId = $lastParam[0];
        $ignoreColumn = (sizeof($lastParam) > 1) ? end($lastParam) : null;

        // Shave of the ignore_id from the array for later processing
        array_pop($parameters);

        return array($ignoreId, $ignoreColumn);
    }
}
