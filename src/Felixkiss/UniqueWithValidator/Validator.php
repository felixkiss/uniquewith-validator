<?php namespace Felixkiss\UniqueWithValidator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Validator
{
    public function validateUniqueWith($attribute, $value, $parameters, $validator)
    {
        $ruleParser = new RuleParser($attribute, $value, $parameters, $validator->getData());

        // The presence verifier is responsible for counting rows within this
        // store mechanism which might be a relational database or any other
        // permanent data store like Redis, etc. We will use it to determine
        // uniqueness.
        $presenceVerifier = $validator->getPresenceVerifier();
        if (method_exists($presenceVerifier, 'setConnection')) {
            $presenceVerifier->setConnection($ruleParser->getConnection());
        }

        return $presenceVerifier->getCount(
            $ruleParser->getTable(),
            $ruleParser->getPrimaryField(),
            $ruleParser->getPrimaryValue(),
            $ruleParser->getIgnoreValue(),
            $ruleParser->getIgnoreColumn(),
            $ruleParser->getAdditionalFields()
        ) == 0;
    }

    public function replaceUniqueWith($message, $attribute, $rule, $parameters, $translator)
    {
        $ruleParser = new RuleParser($attribute, null, $parameters);
        $fields = $ruleParser->getDataFields();

        if (method_exists($translator, 'trans')) {
            $customAttributes = $translator->trans('validation.attributes');
        } else {
            $customAttributes = $translator->get('validation.attributes');
        }

        // Check if translator has custom validation attributes for the fields
        $fields = array_map(function($field) use ($customAttributes) {
            return Arr::get($customAttributes, $field) ?: str_replace('_', ' ', Str::snake($field));
        }, $fields);

        return str_replace(':fields', implode(', ', $fields), $message);
    }
}
