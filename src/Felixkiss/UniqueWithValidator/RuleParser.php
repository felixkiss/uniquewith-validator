<?php namespace Felixkiss\UniqueWithValidator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RuleParser
{
    protected $connection;
    protected $table;
    protected $primaryField;
    protected $primaryValue;
    protected $additionalFields;
    protected $ignoreColumn;
    protected $ignoreValue;
    protected $dataFields;

    protected $attribute;
    protected $parameters;
    protected $data;

    protected $parsed = false;

    public function __construct($attribute = null, $value = null, array $parameters = [], array $data = [])
    {
        $this->primaryField = $this->attribute = $attribute;
        $this->primaryValue = $value;
        $this->parameters = $parameters;
        $this->data = $data;
    }

    protected function parse()
    {
        if ($this->parsed) { return; }
        $this->parsed = true;

        // cleaning: trim whitespace
        $this->parameters = array_map('trim', $this->parameters);

        // first item equals table name
        $this->table = array_shift($this->parameters);
        if (Str::contains($this->table, '.')) {
            list($this->connection, $this->table) = explode('.', $this->table, 2);
        }

        // Check if ignore data is set
        $this->parseIgnore();

        // Parse field data
        $this->parseFieldData();
    }

    protected function parseFieldData()
    {
        $this->additionalFields = [];
        $this->dataFields = [$this->primaryField];

        // Figure out whether field_name is the same as column_name
        // or column_name is explicitly specified.
        //
        // case 1:
        //     $parameter = 'last_name'
        //     => field_name = column_name = 'last_name'
        // case 2:
        //     $parameter = 'last_name=sur_name'
        //     => field_name = 'last_name', column_name = 'sur_name'
        foreach ($this->parameters as $parameter) {
            $parts = array_map('trim', explode('=', $parameter, 2));
            $fieldName = $this->parseFieldName($parts[0]);
            $columnName = count($parts) > 1 ? $parts[1] : $fieldName;
            $this->dataFields[] = $fieldName;

            if ($fieldName === $this->primaryField) {
                $this->primaryField = $columnName;
                continue;
            }

            if (!Arr::has($this->data, $fieldName)) {
                continue;
            }

            $this->additionalFields[$columnName] = Arr::get($this->data, $fieldName);
        }

        $this->dataFields = array_values(array_unique($this->dataFields));
    }

    public function getConnection()
    {
        $this->parse();
        return $this->connection;
    }

    public function getTable()
    {
        $this->parse();
        return $this->table;
    }

    public function getPrimaryField()
    {
        $this->parse();
        return $this->primaryField;
    }

    public function getPrimaryValue()
    {
        $this->parse();
        return $this->primaryValue;
    }

    public function getAdditionalFields()
    {
        $this->parse();
        return $this->additionalFields;
    }

    public function getIgnoreValue()
    {
        $this->parse();
        return $this->ignoreValue;
    }

    public function getIgnoreColumn()
    {
        $this->parse();
        return $this->ignoreColumn;
    }

    public function getDataFields()
    {
        $this->parse();
        return $this->dataFields;
    }

    protected function parseIgnore()
    {
        // Ignore has to be specified as the last parameter
        $lastParameter = end($this->parameters);
        if (!$this->isIgnore($lastParameter)) { return; }

        $lastParameter = array_map('trim', explode('=', $lastParameter));

        $this->ignoreValue = str_replace('ignore:', '', $lastParameter[0]);
        $this->ignoreColumn = (sizeof($lastParameter) > 1) ? end($lastParameter) : null;

        // Shave of the ignore_id from the array for later processing
        array_pop($this->parameters);
    }

    protected function isIgnore($parameter)
    {
        // An ignore_id can be specified by prefixing with 'ignore:'
        if (strpos($parameter, 'ignore:') !== false) {
            return true;
        }

        // An ignore_id can be specified if parameter starts with a
        // number greater than 1 (a valid id in the database)
        $parts = array_map('trim', explode('=', $parameter));
        return preg_match('/^[1-9][0-9]*$/', $parts[0]);
    }

    protected function parseFieldName($field)
    {
        if (preg_match('/^\*\.|\.\*\./', $field)) {
            // This rule validates multiple times, because a wildcard * was used
            // in order to validate all elements of an array. We now need to
            // figure out which element we are on, so we can replace the
            // wildcard with the current index in the array to access the actual
            // data correctly.

            // 1. Convert main attribute (Laravel has already replaced the
            //    wildcards with the current indizes here) to have wildcards
            //    instead
            $attributeWithWildcards = preg_replace(
                ['/^[0-9]+\./', '/\.[0-9]+\./'],
                ['*.', '.*.'],
                $this->attribute
            );

            // 2. Figure out what parts of the current field string should be
            //    replaced (Basically everything before the last wildcard)
            $positionOfLastWildcard = strrpos($attributeWithWildcards, '*.');
            $wildcardPartToBeReplaced = substr($attributeWithWildcards, 0, $positionOfLastWildcard + 2);

            // 3. Figure out what the substitute for the replacement in the
            //    current field string should be (Basically delete everything
            //    after the final index part in the main attribute)
            $endPartToDismiss = substr($attributeWithWildcards, $positionOfLastWildcard + 2);
            $actualIndexPartToBeSubstitute = str_replace($endPartToDismiss, '', $this->attribute);

            // 4. Do the actual replacement. The end result should be a string
            //    of the current field we work on, but with the wildcards
            //    replaced by the correct indizes for the current validation run
            $fieldWithActualIndizes = str_replace($wildcardPartToBeReplaced, $actualIndexPartToBeSubstitute, $field);

            return $fieldWithActualIndizes;
        }

        return $field;
    }
}
