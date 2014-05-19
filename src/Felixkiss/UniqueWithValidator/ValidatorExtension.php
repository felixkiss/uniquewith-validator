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
        $table = $parameters[0];

        // The second parameter position holds the name of the column that
        // needs to be verified as unique. If this parameter isn't specified
        // we will just assume that this column to be verified shares the
        // attribute's name.
        //
        // $column = isset($parameters[1]) ? $parameters[1] : $attribute;
        $column = $attribute;

        // Create $extra array with all other columns, so getCount() will
        // include them as where clauses as well
        $extra = array();

        // Check if last parameter is an integer. If it is, then it will
        // ignore the row with the specified id - useful when updating a row
        list($ignore_id, $ignore_column) = $this->getIgnore($parameters);
        $parameters_length = sizeof($parameters);

        for($i = 1; $i < $parameters_length; $i++)
        {
            // Figure out whether field_name is the same as column_name
            // or column_name is explicitly specified.
            //
            // case 1:
            //     $parameter = 'last_name'
            //     => field_name = column_name = 'last_name'
            // case 2:
            //     $parameter = 'last_name=sur_name'
            //     => field_name = 'last_name', column_name = 'sur_name'
            $parameter = explode('=', $parameters[$i], 2);
            $field_name = trim($parameter[0]);

            if(count($parameter) > 1)
                $column_name = trim($parameter[1]);
            else
                $column_name = $field_name;

            // Figure out whether main field_name has an explicitly specified
            // column_name
            if($field_name == $column)
                $column = $column_name;
            else
                $extra[$column_name] = array_get($this->data, $field_name);
        }

        // The presence verifier is responsible for counting rows within this
        // store mechanism which might be a relational database or any other
        // permanent data store like Redis, etc. We will use it to determine
        // uniqueness.
        $verifier = $this->getPresenceVerifier();

        return $verifier->getCount(

            $table, $column, $value, $ignore_id, $ignore_column, $extra

        ) == 0;
    }

    public function replaceUniqueWith($message, $attribute, $rule, $parameters)
    {
        $fields = array($attribute);
        for($i = 1; $i < sizeof($parameters); $i++)
            $fields[] = $parameters[$i];
        $fields = implode(', ', $fields);
        return str_replace(':fields', $fields, $message);
    }

    /**
     * Returns an array with value and column name for an optional ignore.
     *
     * @param  array $parameters
     * @return array [$ignoreId, $ignoreColumn]
     */
    private function getIgnore(&$parameters)
    {
        $parametersLength = sizeof($parameters);

        if ($parametersLength <= 1)
        {
            return array(null, null);
        }

        $lastParam = $parameters[$parametersLength - 1];
        $lastParamValue = str_replace(" ", "", $lastParam);
        if (preg_match('/^[1-9][0-9]*$/', $lastParamValue))
        {
            $lastParamValue = intval($lastParamValue);
            if ($lastParamValue > 0)
            {
                array_pop($parameters);
                return array($lastParamValue, null);
            }
        }
        else if (preg_match('/^[1-9][0-9]*=.*$/', $lastParamValue))
        {
            list($value, $columnName) = explode('=', $lastParamValue);
            $value = intval(trim($value));
            if ($value > 0)
            {
                array_pop($parameters);
                return array($value, $columnName);
            }
        }

        return array(null, null);
    }
}
