<?php

use Felixkiss\UniqueWithValidator\ValidatorExtension;

class ValidatorExtensionTest extends PHPUnit_Framework_TestCase
{
    protected $translator;
    protected $data;
    protected $rules;
    protected $messages;
    protected $presenceVerifier;
    protected $testDefaultErrorMessage = 'This is a test error message with :fields.';

    public function setUp()
    {
        $this->translator = Mockery::mock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->shouldReceive('get')
            ->with('uniquewith-validator::validation.unique_with')
            ->andReturn($this->testDefaultErrorMessage);
        $this->translator->shouldReceive('trans')
            ->andReturnUsing(function($arg) { return $arg; });

        $this->rules = array(
            'first_name' => 'unique_with:users,last_name',
        );

        $this->presenceVerifier = Mockery::mock('Illuminate\Validation\PresenceVerifierInterface');

        $this->messages = array();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testValidatesNewCombination()
    {
        $this->data = array(
            'first_name' => 'Foo',
            'last_name' => 'Bar',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        // No existing Object with this parameter set
        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   null,
                   null,
                   array('last_name' => 'Bar')
               )
             ->once()
             ->andReturn(0);

        $this->assertFalse($validator->fails());
    }

    public function testValidatesExistingCombination()
    {
        $this->data = array(
            'first_name' => 'Foo',
            'last_name' => 'Bar',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        // One existing Object with this parameter set
        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   null,
                   null,
                   array('last_name' => 'Bar')
               )
             ->once()
             ->andReturn(1);

        $this->assertTrue($validator->fails());
    }

    public function testDefaultErrorMessageWorks()
    {
        $this->setUpFailCaseTest();

        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );

        $custom_messages = $validator->getCustomMessages();
        $this->assertArrayHasKey('unique_with', $custom_messages);
        $this->assertEquals($custom_messages['unique_with'], $this->testDefaultErrorMessage);

        $validator->setPresenceVerifier($this->presenceVerifier);

        // One existing Object with this parameter set
        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   null,
                   null,
                   array('last_name' => 'Bar')
               )
             ->once()
             ->andReturn(1);

        $errors = $validator->messages();
        $this->assertTrue(is_object($errors), 'Asserting that $validator->messages() returns an object.');
        $this->assertInstanceOf('Illuminate\Support\MessageBag', $errors);

        $errors = $errors->toArray();
        $this->assertArrayHasKey('first_name', $errors);
        $this->assertNotEmpty($errors['first_name']);
    }

    public function testErrorMessageOverrideWorks()
    {
        $test_message = 'This is a test override message with :fields.';

        $this->setUpFailCaseTest();

        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            array('unique_with' => $test_message)
        );

        $custom_messages = $validator->getCustomMessages();
        $this->assertArrayHasKey('unique_with', $custom_messages);
        $this->assertEquals($custom_messages['unique_with'], $test_message);

        $validator->setPresenceVerifier($this->presenceVerifier);

        // One existing Object with this parameter set
        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   null,
                   null,
                   array('last_name' => 'Bar')
               )
             ->once()
             ->andReturn(1);

        $errors = $validator->messages();
        $this->assertTrue(is_object($errors), 'Asserting that $validator->messages() returns an object.');
        $this->assertInstanceOf('Illuminate\Support\MessageBag', $errors);

        $errors = $errors->toArray();
        $this->assertArrayHasKey('first_name', $errors);
        $this->assertTrue(isset($errors['first_name'][0]));
        $this->assertEquals('This is a test override message with first name, last name.', $errors['first_name'][0]);
    }

    public function testFieldsDoNotIncludeIDFieldInErrorMessage()
    {
        $this->setUpFailCaseTest();

        $this->rules['first_name'] .= ',135899999';

        $errors = $this->getValidatorExtensionMessages();

        $this->assertTrue(strpos($errors['first_name'][0], '135899999') === false,
            'Asserting that ID is not found in error message.');
    }

    public function testFieldsDoNotIncludeIDFieldInErrorMessageWithColumnSpecifier()
    {
        $this->setUpFailCaseTest();

        $this->rules['first_name'] .= ',135899999=zyxtuvabc';

        $errors = $this->getValidatorExtensionMessages();

        $this->assertTrue(strpos($errors['first_name'][0], '135899999') === false,
            'Asserting that ID is not found in error message.');
        $this->assertTrue(strpos($errors['first_name'][0], 'zyxtuvabc') === false,
            'Asserting that ID column is not found in error message.');
    }

    public function testValidatesNewCombinationWithMoreThanTwoFields()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,middle_name,last_name',
        );
        $this->data = array(
            'first_name' => 'Foo',
            'middle_name' => 'Bar',
            'last_name' => 'Baz',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        // No existing Object with this parameter set
        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   null,
                   null,
                   array('middle_name' => 'Bar', 'last_name' => 'Baz')
               )
             ->once()
             ->andReturn(0);

        $this->assertFalse($validator->fails());
    }

    public function testValidatesExistingCombinationWithMoreThanTwoFields()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,middle_name,last_name',
        );
        $this->data = array(
            'first_name' => 'Foo',
            'middle_name' => 'Bar',
            'last_name' => 'Baz',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        // One existing Object with this parameter set
        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   null,
                   null,
                   array('middle_name' => 'Bar', 'last_name' => 'Baz')
               )
             ->once()
             ->andReturn(1);

        $this->assertTrue($validator->fails());
    }

    public function testReadsParametersWithoutExplicitColumnNames()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,middle_name,last_name'
        );
        $this->data = array(
            'first_name' => 'Foo',
            'middle_name' => 'Bar',
            'last_name' => 'Baz',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   null,
                   null,
                   array('middle_name' => 'Bar', 'last_name' => 'Baz')
               )
             ->once();

        $validator->fails();
    }

    public function testReadsParametersWithExplicitColumnNames()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,middle_name = mid_name,last_name=sur_name'
        );
        $this->data = array(
            'first_name' => 'Foo',
            'middle_name' => 'Bar',
            'last_name' => 'Baz',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   null,
                   null,
                   array('mid_name' => 'Bar', 'sur_name' => 'Baz')
               )
             ->once();

        $validator->fails();
    }

    public function testReadsPrimaryParameterWithExplicitColumnNames()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,first_name = name,middle_name,last_name=sur_name'
        );
        $this->data = array(
            'first_name' => 'Foo',
            'middle_name' => 'Bar',
            'last_name' => 'Baz',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'name',
                   'Foo',
                   null,
                   null,
                   array('middle_name' => 'Bar', 'sur_name' => 'Baz')
               )
             ->once();

        $validator->fails();
    }

    public function testValidatesExistingCombinationWithIgnoreID()
    {
        $this->data = array(
            'first_name' => 'Foo',
            'last_name' => 'Bar',
        );
        $this->rules = array(
            'first_name' => 'unique_with:users,last_name,1'
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        // One existing Object with this parameter set
        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   1,
                   null,
                   array('last_name' => 'Bar')
               )
             ->once()
             ->andReturn(0);

        $this->assertFalse($validator->fails());
    }

    public function testValidatesNewCombinationWithMoreThanTwoFieldsWithIgnoreID()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,middle_name,last_name,1'
        );
        $this->data = array(
            'first_name' => 'Foo',
            'middle_name' => 'Bar',
            'last_name' => 'Baz',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        // No existing Object with this parameter set
        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   1,
                   null,
                   array('middle_name' => 'Bar', 'last_name' => 'Baz')
               )
             ->once()
             ->andReturn(0);

        $this->assertFalse($validator->fails());
    }

    public function testReadsParametersWithExplicitColumnNamesWithIgnoreID()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,middle_name = mid_name,last_name=sur_name,1'
        );
        $this->data = array(
            'first_name' => 'Foo',
            'middle_name' => 'Bar',
            'last_name' => 'Baz',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   1,
                   null,
                   array('mid_name' => 'Bar', 'sur_name' => 'Baz')
               )
             ->once();

        $validator->fails();
    }


    public function testReadsPrimaryParameterWithExplicitColumnNamesWithIgnoreID()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,first_name = name,middle_name,last_name=sur_name,1'
        );
        $this->data = array(
            'first_name' => 'Foo',
            'middle_name' => 'Bar',
            'last_name' => 'Baz',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'name',
                   'Foo',
                   1,
                   null,
                   array('middle_name' => 'Bar', 'sur_name' => 'Baz')
               )
             ->once();

        $validator->fails();
    }

    public function testCustomColumnNameForIgnoreId()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,first_name,last_name,1 = UserKey',
        );
        $this->data = array(
            'first_name' => 'Foo',
            'last_name'  => 'Bar',
        );
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );
        $validator->setPresenceVerifier($this->presenceVerifier);

        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->with(
                   'users',
                   'first_name',
                   'Foo',
                   1,
                   'UserKey',
                   array('last_name' => 'Bar')
               )
             ->once();

        $validator->fails();
    }

    public function setUpFailCaseTest()
    {
        $this->testValidatesExistingCombination();

        $this->data = array(
            'first_name' => 'Foo',
            'last_name' => 'Bar',
        );
    }

    public function getValidatorExtensionMessages()
    {
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );

        $validator->setPresenceVerifier($this->presenceVerifier);

        // One existing Object with this parameter set
        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->once()
             ->andReturn(1);

        $errors = $validator->messages();
        $this->assertTrue(is_object($errors), 'Asserting that $validator->messages() returns an object.');

        $errors = $errors->toArray();
        $this->assertArrayHasKey('first_name', $errors);

        return $errors;
    }
}
