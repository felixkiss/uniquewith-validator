<?php

use Felixkiss\UniqueWithValidator\ValidatorExtension;

class ValidatorExtensionTest extends PHPUnit_Framework_TestCase
{
    protected $translator;
    protected $data;
    protected $rules;
    protected $messages;
    protected $presenceVerifier;
    protected $defaultErrorMessage = 'This is a test error message with :fields.';

    public function setUp()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,last_name',
        );
        $this->messages = array();

        $this->translator = $this->mockTranslator();
        $this->presenceVerifier = Mockery::mock('Illuminate\Validation\PresenceVerifierInterface');
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
        $validator = $this->createValidator();

        // No existing Object with this parameters set
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
        $validator = $this->createValidator();

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
        $this->data = array(
            'first_name' => 'Foo',
            'last_name' => 'Bar',
        );
        $validator = $this->createValidator();

        $this->setUpFailWithExistingCombination();

        $errors = $validator->getMessageBag()->toArray();
        $this->assertEquals(str_replace(':fields', 'first name, last name', $this->defaultErrorMessage), $errors['first_name'][0]);
    }

    public function testErrorMessageOverrideWorks()
    {
        $customErrorMessage = 'This is a test override message with :fields.';

        $this->data = array(
            'first_name' => 'Foo',
            'last_name' => 'Bar',
        );
        $this->messages = array('unique_with' => $customErrorMessage);
        $validator = $this->createValidator();

        $this->setUpFailWithExistingCombination();

        $errors = $validator->getMessageBag()->toArray();
        $this->assertEquals(str_replace(':fields', 'first name, last name', $customErrorMessage), $errors['first_name'][0]);
    }

    public function testFieldsDoNotIncludeIDFieldInErrorMessage()
    {
        $this->data = array(
            'first_name' => 'Foo',
            'last_name' => 'Bar',
        );
        $this->rules['first_name'] .= ',135899999';
        $validator = $this->createValidator();

        $this->setUpFailWithExistingCombination();

        $errors = $validator->getMessageBag()->toArray();

        $this->assertNotContains('135899999', $errors['first_name'][0], 'Asserting that ID is not found in error message.');
    }

    public function testFieldsDoNotIncludeIDFieldInErrorMessageWithColumnSpecifier()
    {
        $this->data = array(
            'first_name' => 'Foo',
            'last_name' => 'Bar',
        );
        $this->rules['first_name'] .= ',135899999=zyxtuvabc';
        $validator = $this->createValidator();

        $this->setUpFailWithExistingCombination();

        $errors = $validator->getMessageBag()->toArray();

        $this->assertNotContains('135899999', $errors['first_name'][0], 'Asserting that ID is not found in error message.');
        $this->assertNotContains('zyxtuvabc', $errors['first_name'][0], 'Asserting that ID column is not found in error message.');
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
        $validator = $this->createValidator();

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
        $validator = $this->createValidator();

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
        $validator = $this->createValidator();

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

    public function testReadsIgnoreIdWithDefaultColumnName()
    {
        $this->data = array(
            'first_name' => 'Foo',
            'last_name' => 'Bar',
        );
        $this->rules = array(
            'first_name' => 'unique_with:users,last_name,1'
        );
        $validator = $this->createValidator();

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
             ->once();

        $validator->fails();
    }

    public function testReadsIgnoreIdWithCustomColumnName()
    {
        $this->rules = array(
            'first_name' => 'unique_with:users,first_name,last_name,1 = UserKey',
        );
        $this->data = array(
            'first_name' => 'Foo',
            'last_name'  => 'Bar',
        );
        $validator = $this->createValidator();

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

    protected function createValidator()
    {
        $validator = new ValidatorExtension(
            $this->translator,
            $this->data,
            $this->rules,
            $this->messages
        );

        $validator->setPresenceVerifier($this->presenceVerifier);

        return $validator;
    }

    protected function setUpFailWithExistingCombination()
    {
        // One existing Object with this parameter set
        $this->presenceVerifier
             ->shouldReceive('getCount')
             ->once()
             ->andReturn(1);
    }

    protected function mockTranslator()
    {
        try {
            $classInfo = new ReflectionClass('Illuminate\Contracts\Translation\Translator');
            $translator = Mockery::mock('Illuminate\Contracts\Translation\Translator');
        }
        catch(ReflectionException $e) {
            $translator = Mockery::mock('Symfony\Component\Translation\TranslatorInterface');
        }

        $translator->shouldReceive('get')
            ->with('uniquewith-validator::validation.unique_with')
            ->andReturn($this->defaultErrorMessage);
        $translator->shouldReceive('trans')->andReturnUsing(function($arg) { return $arg; });

        return $translator;
    }
}
