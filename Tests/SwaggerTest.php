<?php namespace Draw\Swagger\Tests;

use Draw\Swagger\Swagger;
use PHPUnit\Framework\TestCase;

class SwaggerTest extends TestCase
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
        $this->assertInstanceOf(\Draw\Swagger\Schema\Swagger::class, $schema);

        $this->assertJsonStringEqualsJsonString(file_get_contents($file), $swagger->dump($schema, false));
    }
}