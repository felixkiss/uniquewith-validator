<?php namespace spec\Felixkiss\UniqueWithValidator;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Validation\DatabasePresenceVerifier;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ValidatorSpec extends ObjectBehavior
{
    private $translator;
    private $presenceVerifier;
    private $validator;
    private $validationMessage;

    function let(Translator $translator, PresenceVerifierInterface $presenceVerifier)
    {
        $this->translator = $translator;
        $this->presenceVerifier = $presenceVerifier;

        $this->trans(Argument::cetera())->willReturnArgument(0);
        $this->setValidationMessage('This combination of :fields already exists.');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Felixkiss\UniqueWithValidator\Validator');
    }

    function it_passes_validation_if_presence_verifier_reports_no_existing_database_rows()
    {
        $this->presenceVerifier->getCount(Argument::cetera())->willReturn(0);

        $this->validateData(
            ['first_name' => 'unique_with:users,last_name'],
            [
                'first_name' => 'Foo',
                'last_name' => 'Bar',
            ]
        )->shouldReturn(true);
    }

    function it_fails_validation_if_presence_verifier_reports_existing_database_rows()
    {
        $this->presenceVerifier->getCount(Argument::cetera())->willReturn(1);

        $this->validateData(
            ['first_name' => 'unique_with:users,last_name'],
            [
                'first_name' => 'Foo',
                'last_name' => 'Bar',
            ]
        )->shouldReturn(false);
    }

    function it_checks_presence_of_a_simple_two_field_combination_correctly()
    {
        $this->validateData(
            ['first_name' => 'unique_with:users,last_name'],
            [
                'first_name' => 'Foo',
                'last_name' => 'Bar',
            ]
        );

        $this->presenceVerifier->getCount(
            'users',
            'first_name',
            'Foo',
            null,
            null,
            ['last_name' => 'Bar']
        )->shouldHaveBeenCalled();
    }

    function it_reads_parameters_without_explicit_column_names()
    {
        $this->validateData(
            ['first_name' => 'unique_with:users,middle_name,last_name'],
            [
                'first_name' => 'Foo',
                'middle_name' => 'Bar',
                'last_name' => 'Baz',
            ]
        );

        $this->presenceVerifier->getCount(
            'users',
            'first_name',
            'Foo',
            null,
            null,
            ['middle_name' => 'Bar', 'last_name' => 'Baz']
        )->shouldHaveBeenCalled();
    }

    function it_reads_parameters_with_explicit_column_names()
    {
        $this->validateData(
            ['first_name' => 'unique_with:users,first_name = name,middle_name = mid_name,last_name=sur_name'],
            [
                'first_name' => 'Foo',
                'middle_name' => 'Bar',
                'last_name' => 'Baz',
            ]
        );

        $this->presenceVerifier->getCount(
            'users',
            'name',
            'Foo',
            null,
            null,
            ['mid_name' => 'Bar', 'sur_name' => 'Baz']
        )->shouldHaveBeenCalled();
    }

    function it_reads_implicit_integer_ignore_id_with_default_column_name()
    {
        $this->validateData(
            ['first_name' => 'unique_with:users,last_name,1'],
            [
                'first_name' => 'Foo',
                'last_name' => 'Bar',
            ]
        );

        $this->presenceVerifier->getCount(
            'users',
            'first_name',
            'Foo',
            1,
            null,
            ['last_name' => 'Bar']
        )->shouldHaveBeenCalled();
    }

    function it_reads_implicit_integer_ignore_id_with_custom_column_name()
    {
        $this->validateData(
            ['first_name' => 'unique_with:users,first_name,last_name,1 = UserKey'],
            [
                'first_name' => 'Foo',
                'last_name' => 'Bar',
            ]
        );

        $this->presenceVerifier->getCount(
            'users',
            'first_name',
            'Foo',
            1,
            'UserKey',
            ['last_name' => 'Bar']
        )->shouldHaveBeenCalled();
    }

    function it_reads_explicit_ignore_id_with_default_column_name()
    {
        $this->validateData(
            ['first_name' => 'unique_with:users,last_name,ignore:1'],
            [
                'first_name' => 'Foo',
                'last_name' => 'Bar',
            ]
        );

        $this->presenceVerifier->getCount(
            'users',
            'first_name',
            'Foo',
            1,
            null,
            ['last_name' => 'Bar']
        )->shouldHaveBeenCalled();
    }

    function it_reads_explicit_ignore_id_with_custom_column_name()
    {
        $this->validateData(
            ['first_name' => 'unique_with:users,first_name,last_name,ignore:1 = UserKey'],
            [
                'first_name' => 'Foo',
                'last_name' => 'Bar',
            ]
        );

        $this->presenceVerifier->getCount(
            'users',
            'first_name',
            'Foo',
            1,
            'UserKey',
            ['last_name' => 'Bar']
        )->shouldHaveBeenCalled();
    }

    function it_replaces_fields_in_error_message_correctly()
    {
        $this->presenceVerifier->getCount(Argument::cetera())->willReturn(1);
        $this->trans('validation.attributes')->shouldBeCalled()->willReturn([]);

        $this->validateData(
            ['first_name' => 'unique_with:users,first_name,last_name'],
            [
                'first_name' => 'Foo',
                'last_name' => 'Bar',
            ]
        );

        $expectedErrorMessage = str_replace(':fields', 'first name, last name', $this->getValidationMessage());
        expect($this->validator->getMessageBag()->toArray())->toBe(['first_name' => [$expectedErrorMessage]]);
    }

    function it_uses_custom_error_message_coming_from_translator()
    {
        $customErrorMessage = 'Error: Found combination of :fields in database.';

        $this->presenceVerifier->getCount(Argument::cetera())->willReturn(1);
        $this->trans('uniquewith-validator::validation.unique_with')->shouldBeCalled()
            ->willReturn($customErrorMessage);
        $this->trans('validation.attributes')->shouldBeCalled()->willReturn([]);

        $this->validateData(
            ['first_name' => 'unique_with:users,first_name,middle_name,last_name'],
            [
                'first_name' => 'Foo',
                'middle_name' => 'Bar',
                'last_name' => 'Baz',
            ]
        );

        $expectedErrorMessage = str_replace(':fields', 'first name, middle name, last name', $customErrorMessage);
        expect($this->validator->getMessageBag()->toArray())->toBe(['first_name' => [$expectedErrorMessage]]);
    }

    function it_uses_custom_attribute_names_coming_from_translator()
    {
        $this->presenceVerifier->getCount(Argument::cetera())->willReturn(1);

        $this->trans('validation.attributes')->shouldBeCalled()->willReturn([
            'first_name' => 'Vorname',
            'last_name' => 'Nachname',
        ]);

        $this->validateData(
            ['first_name' => 'unique_with:users,first_name,last_name'],
            [
                'first_name' => 'Foo',
                'last_name' => 'Bar',
            ]
        );

        $expectedErrorMessage = str_replace(':fields', 'Vorname, Nachname', $this->getValidationMessage());
        expect($this->validator->getMessageBag()->toArray())->toBe(['first_name' => [$expectedErrorMessage]]);
    }

    function it_supports_dot_notation_for_an_object_in_rules()
    {
        $this->validateData(
            ['name.first' => 'unique_with:users, name.first = first_name, name.last = last_name'],
            [
                'name' => [
                    'first' => 'Foo',
                    'last' => 'Bar',
                ],
            ]
        );

        $this->presenceVerifier->getCount(
            'users',
            'first_name',
            'Foo',
            null,
            null,
            ['last_name' => 'Bar']
        )->shouldHaveBeenCalled();
    }

    function it_supports_dot_notation_for_an_array_in_rules()
    {
        $this->validateData(
            ['users.*.first' => 'unique_with:users, users.*.first = first_name, users.*.last = last_name'],
            [
                'users' => [
                    [
                        'first' => 'Foo',
                        'last' => 'Bar',
                    ],
                    [
                        'first' => 'Baz',
                        'last' => 'Quux',
                    ],
                ],
            ]
        );

        $this->presenceVerifier->getCount(
            'users',
            'first_name',
            'Foo',
            null,
            null,
            ['last_name' => 'Bar']
        )->shouldHaveBeenCalled();

        $this->presenceVerifier->getCount(
            'users',
            'first_name',
            'Baz',
            null,
            null,
            ['last_name' => 'Quux']
        )->shouldHaveBeenCalled();
    }

    function it_uses_connection_if_specified(DatabasePresenceVerifier $dbVerifier)
    {
        $this->presenceVerifier = $dbVerifier;

        $this->validateData(
            ['first_name' => 'unique_with:db.users,middle_name,last_name'],
            [
                'first_name' => 'Foo',
                'middle_name' => 'Bar',
                'last_name' => 'Baz',
            ]
        );

        $this->presenceVerifier->setConnection('db')->shouldHaveBeenCalled();
        $this->presenceVerifier->getCount(
            'users',
            'first_name',
            'Foo',
            null,
            null,
            ['middle_name' => 'Bar', 'last_name' => 'Baz']
        )->shouldHaveBeenCalled();
    }

    protected function validateData(array $rules = [], array $data = [])
    {
        $result = null;

        if (method_exists($this->translator->getWrappedObject(), 'trans')) {
            $message = $this->translator->getWrappedObject()->trans('uniquewith-validator::validation.unique_with');
        } else {
            $message = $this->translator->getWrappedObject()->get('uniquewith-validator::validation.unique_with');
        }
        $factory = new Factory($this->translator->getWrappedObject());
        $factory->extend('unique_with', function() use (&$result) {
            $result = call_user_func_array([$this, 'validateUniqueWith'], func_get_args());
        }, $message);
        $factory->replacer('unique_with', function() {
            $arguments = func_get_args();
            if (sizeof($arguments) >= 5) {
                $arguments[4] = $arguments[4]->getTranslator();
            }
            else {
                $arguments[4] = $this->translator->getWrappedObject();
            }

            return call_user_func_array([$this, 'replaceUniqueWith'], $arguments)->getWrappedObject();
        });
        $factory->setPresenceVerifier($this->presenceVerifier->getWrappedObject());

        $validator = $factory->make($data, $rules);
        $validator->passes();

        $this->validator = $validator;

        return $result;
    }

    protected function setValidationMessage($message)
    {
        $this->validationMessage = $message;
        $this->trans('uniquewith-validator::validation.unique_with')->willReturn($message);
    }

    protected function getValidationMessage()
    {
        return $this->validationMessage;
    }

    /**
     * Pass the trans call dynamically to the translator.
     *
     * @param  mixed  ...$args
     * @return mixed
     */
    protected function trans(...$args)
    {
        if (method_exists($this->translator->getWrappedObject(), 'trans')) {
            return $this->translator->trans(...$args);
        } else {
            return $this->translator->get(...$args);
        }
    }
}
