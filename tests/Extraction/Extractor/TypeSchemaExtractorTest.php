<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 30/09/17
 * Time: 10:31 PM
 */

namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContext;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Schema\Schema;
use Draw\Swagger\Swagger;

class TypeSchemaExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function provideTestCanExtract()
    {
        return array(
            array('string', null, false),
            array(null, new Schema(), false),
            array('string', new Schema(), true),
            array('string[]', new Schema(), true),
            array(new Schema(), new Schema(), false),
        );
    }

    /**
     * @dataProvider provideTestCanExtract
     *
     * @param $source
     * @param $type
     * @param $expected
     */
    public function testCanExtract($source, $type, $canBeExtract)
    {
        $extractor = new TypeSchemaExtractor();

        $context = $this->getMock('Draw\Swagger\Extraction\ExtractionContextInterface');

        $this->assertSame($canBeExtract, $extractor->canExtract($source, $type, $context));

        if (!$canBeExtract) {
            try {
                $extractor->extract($source, $type, $context);
                $this->fail('should throw a exception of type [Draw\Swagger\Extraction\ExtractionImpossibleException]');
            } catch (ExtractionImpossibleException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function testExtract()
    {
        $extractor = new TypeSchemaExtractor();

        $context = $this->getExtractionContext();
        $context->getSwagger()->registerExtractor($extractor);

        $schema = $context->getRootSchema();

        $schema->addDefinition("fake-string", $modelSchema = new Schema());
        $extractor->extract("string", $modelSchema, $context);

        $schema->addDefinition("fake-strings", $modelSchema = new Schema());
        $extractor->extract("string[]", $modelSchema, $context);

        $schema->addDefinition("fake-strings", $modelSchema = new Schema());
        $extractor->extract("string[]", $modelSchema, $context);

        $schema->addDefinition("object", $modelSchema = new Schema());
        $extractor->extract(TypeExtractorStubModel::class, $modelSchema, $context);

        $jsonSchema = $context->getSwagger()->dump($context->getRootSchema(), false);

        $this->assertJsonStringEqualsJsonString(
            file_get_contents(__DIR__ . '/fixture/typeSchemaExtractorTestExtract.json'),
            $jsonSchema
        );
    }

    public function getExtractionContext()
    {
        $swagger = new Swagger();
        $schema = $swagger->extract('{"swagger":"2.0","definitions":{}}');

        return new ExtractionContext($swagger, $schema);
    }
}

class TypeExtractorStubModel
{

}