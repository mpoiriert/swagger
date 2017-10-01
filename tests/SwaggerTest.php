<?php

namespace Draw\Swagger;

use JMS\Serializer\SerializerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SwaggerTest extends \PHPUnit_Framework_TestCase
{
    public function provideTestExtractSwaggerSchema()
    {
        $result = array();
        foreach(glob(__DIR__ . '/fixture/schema/*.json') as $file) {
            $result[] = array($file);
        }

        return $result;
    }

    /**
     * @dataProvider provideTestExtractSwaggerSchema
     * @param $file
     */
    public function testExtractSwaggerSchema($file)
    {
        $swagger = new Swagger();

        $schema = $swagger->extract(file_get_contents($file));
        $this->assertInstanceOf('Draw\Swagger\Schema\Swagger', $schema);

        $this->assertJsonStringEqualsJsonString(file_get_contents($file), $swagger->dump($schema, false));
    }
}