<?php namespace Draw\Swagger\Tests;

use Draw\Swagger\SchemaCleaner;
use Draw\Swagger\Swagger;
use PHPUnit\Framework\TestCase;

class SchemaCleanerTest extends TestCase
{
    /**
     * @var SchemaCleaner
     */
    private $schemaCleaner;

    public function setUp()
    {
        $this->schemaCleaner = new SchemaCleaner();
    }

    public function provideTestClean()
    {
        return [
            'simple' => ['simple'],
            'difference' => ['difference'],
            'deep-reference' => ['deep-reference']
        ];
    }

    /**
     * @dataProvider provideTestClean
     *
     * @param $case
     */
    public function testClean($case)
    {
        $swagger = new Swagger();
        $schema = $swagger->extract(
            file_get_contents(__DIR__ . '/fixture/cleaner/' . $case . '-dirty.json')
        );
        $this->assertInstanceOf(\Draw\Swagger\Schema\Swagger::class, $schema);

        $cleanedSchema = $this->schemaCleaner->clean($schema);

        $this->assertEquals(
            json_decode(file_get_contents(__DIR__ . '/fixture/cleaner/' . $case . '-clean.json'), true),
            json_decode($swagger->dump($cleanedSchema, false), true)
        );
    }
}