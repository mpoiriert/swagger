<?php

namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContext;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Schema\Operation;
use Draw\Swagger\Schema\PathItem;
use Draw\Swagger\Swagger;

class PhpDocOperationExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function provideTestCanExtract()
    {
        $reflectionMethod = new \ReflectionMethod(__NAMESPACE__ . '\PhpDocOperationExtractorStubService', 'operation');

        return array(
            array(null, null, false),
            array(null, new Operation(), false),
            array($reflectionMethod, null, false),
            array($reflectionMethod, new Operation(), true),
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
        $extractor = new PhpDocOperationExtractor();
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
        $extractor = new PhpDocOperationExtractor();
        $extractor->registerExceptionResponseCodes('Draw\Swagger\Extraction\ExtractionImpossibleException', 400);
        $extractor->registerExceptionResponseCodes('LengthException', 408, 'Define message');
        $reflectionMethod = new \ReflectionMethod(__NAMESPACE__ . '\PhpDocOperationExtractorStubService', 'operation');

        $context = $this->getExtractionContext();
        $context->getSwagger()->registerExtractor(new TypeSchemaExtractor());
        $schema = $context->getRootSchema();
        $schema->paths['/service'] = $pathItem = new PathItem();

        $pathItem->get = $operation = new Operation();

        $extractor->extract($reflectionMethod, $operation, $context);

        $this->assertJsonStringEqualsJsonString(
            file_get_contents(__DIR__ . '/fixture/phpDocOperationExtractorExtract.json'),
            $context->getSwagger()->dump($context->getRootSchema(), false)
        );
    }

    public function getExtractionContext()
    {
        $swagger = new Swagger();
        $schema = $swagger->extract('{"swagger":"2.0","definitions":{}}');

        return new ExtractionContext($swagger, $schema);
    }
}

class PhpDocOperationExtractorStubService
{
    /**
     * @param PhpDocOperationExtractorStubService $service
     * @param $string
     * @param array $array
     *
     * @return \Draw\Swagger\Extraction\Extractor\PhpDocOperationExtractorStubService
     *
     * @throws \Exception When problem occur
     * @throws \LengthException
     * @throws \Draw\Swagger\Extraction\ExtractionImpossibleException
     */
    public function operation(PhpDocOperationExtractorStubService $service, $string, array $array)
    {
    }
}