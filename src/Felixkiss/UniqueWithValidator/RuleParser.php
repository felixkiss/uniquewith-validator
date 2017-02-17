<?php namespace Felixkiss\UniqueWithValidator;

class RuleParser
{
    protected $table;
    protected $primaryField;
    protected $primaryValue;
    protected $additionalFields;
    protected $ignoreColumn;
    protected $ignoreValue;
    protected $dataFields;

    protected $parameters;
    protected $data;

    protected $parsed = false;

    public function __construct($attribute = null, $value = null, array $parameters = [], array $data = [])
    {
        $this->primaryField = $attribute;
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
            $fieldName = $parts[0];
            $columnName = count($parts) > 1 ? $parts[1] : $fieldName;
            $this->dataFields[] = $fieldName;

            if ($fieldName === $this->primaryField) {
                $this->primaryField = $columnName;
                continue;
            }

            if (!array_key_exists($fieldName, $this->data)) {
                continue;
            }

            $this->additionalFields[$columnName] = $this->data[$fieldName];
        }

        $this->dataFields = array_values(array_unique($this->dataFields));
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
}
