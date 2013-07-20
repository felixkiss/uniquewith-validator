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

        // The second parameter position holds the name of the column that needs to
        // be verified as unique. If this parameter isn't specified we will just
        // assume that this column to be verified shares the attribute's name.
        // $column = isset($parameters[1]) ? $parameters[1] : $attribute;
        $column = $attribute;

        // Create $extra array with all other columns, so getCount() will include
        // them as where clauses as well
        $extra = array();

        for($i = 1; $i < sizeof($parameters); $i++)
        {
            $extra[$parameters[$i]] = array_get($this->data, $parameters[$i]);
        }

        // The presence verifier is responsible for counting rows within this store
        // mechanism which might be a relational database or any other permanent
        // data store like Redis, etc. We will use it to determine uniqueness.
        $verifier = $this->getPresenceVerifier();

        return $verifier->getCount(

            $table, $column, $value, null, null, $extra

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
}