<?php

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $classInfo = new ReflectionClass('Illuminate\Contracts\Translation\Translator');
}
catch(ReflectionException $e) {
    $code = <<<CODE
namespace Illuminate\Contracts\Translation;

interface Translator extends \Symfony\Component\Translation\TranslatorInterface
{
}
CODE;
    eval($code);
}
