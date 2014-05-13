<?php namespace Felixkiss\UniqueWithValidator;

use Illuminate\Validation\Validator;

class ValidatorExtension extends Validator
{
    /**
     * Creates a new instance of ValidatorExtension
     */
    public function __construct($translator, $data, $rules, $messages)
    {
        parent::__construct($translator, $data, $rules, $messages);
    }

    /**
     * Usage: unique_with: table, column1, column2, ...
     *
     * @param  [type] $attribute  [description]
     * @param  [type] $value      [description]
     * @param  [type] $parameters [description]
     * @return [type]             [description]
     */
    public function validateUniqueWith($attribute, $value, $parameters)
    {
        // cleaning: remove all " "
        $parameters = str_replace(" ","",$parameters);

        // first item equals table name
        $table = array_shift($parameters);

        // The second parameter position holds the name of the column that needs to
        // be verified as unique. If this parameter isn't specified we will just
        // assume that this column to be verified shares the attribute's name.
        // $column = isset($parameters[1]) ? $parameters[1] : $attribute;
        $column = $attribute;

        // Create $extra array with all other columns, so getCount() will include
        // them as where clauses as well
        $extra = array();

        // Check if last parameter is an integer. If it is, then it will ignore the row with the specified id - useful when updating a row
        $ignore_id = null;

        $last_param = end($parameters);
        if(preg_match('/^[1-9][0-9]*$/', $last_param))
        {
            // last_param is a number > 0 and pop that from attributes list
            $ignore_id = intval(array_pop($parameters));
        }

        // Figure out whether field_name is the same as column_name
        // or column_name is explicitly specified.
        //
        // case 1:
        //     $parameter = 'last_name'
        //     => field_name = column_name = 'last_name'
        // case 2:
        //     $parameter = 'last_name=sur_name'
        //     => field_name = 'last_name', column_name = 'sur_name'
        foreach($parameters as $parameter) {
            $parameter = explode('=', $parameter , 2);
            $field_name = $parameter[0];

            if (count($parameter) > 1)
                $column_name = $parameter[1];
            else
                $column_name = $field_name;

            // Figure out whether main field_name has an explicitly specified column_name
            if ($field_name == $column)
                $column = $column_name;
            else
                $extra[$column_name] = array_get($this->data, $field_name);
        }

        // The presence verifier is responsible for counting rows within this store
        // mechanism which might be a relational database or any other permanent
        // data store like Redis, etc. We will use it to determine uniqueness.
        $verifier = $this->getPresenceVerifier();

        return $verifier->getCount(

            $table, $column, $value, $ignore_id, null, $extra

        ) == 0;
    }

    public function replaceUniqueWith($message, $attribute, $rule, $parameters)
    {
        // merge primary field with conditional fields
        $fields = array($attribute) + $parameters;

        // get full language support due mapping to validator getAttritube function
        $fields = array_map(array($this, 'getAttribute'), $fields);

        // fields to string
        $fields = implode(', ', $fields);

        return str_replace(':fields', $fields, $message);
    }
}
