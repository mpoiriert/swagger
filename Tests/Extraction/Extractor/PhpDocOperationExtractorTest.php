<?php namespace Draw\Swagger\Tests\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContext;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\Extractor\PhpDocOperationExtractor;
use Draw\Swagger\Extraction\Extractor\TypeSchemaExtractor;
use Draw\Swagger\Schema\Operation;
use Draw\Swagger\Schema\PathItem;
use Draw\Swagger\Swagger;
use Exception;
use LengthException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PhpDocOperationExtractorTest extends TestCase
{
    /**
     * @var PhpDocOperationExtractor
     */
    private $phpDocOperationExtractor;

    public function setUp()
    {
        $this->phpDocOperationExtractor = new PhpDocOperationExtractor();
    }

    public function provideTestCanExtract()
    {
        $reflectionMethod = new ReflectionMethod(__NAMESPACE__ . '\PhpDocOperationExtractorStubService', 'operation');

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
     * @param $canBeExtract
     */
    public function testCanExtract($source, $type, $canBeExtract)
    {
        /** @var ExtractionContextInterface $context */
        $context = $this->getMockForAbstractClass(ExtractionContextInterface::class);

        $this->assertSame($canBeExtract, $this->phpDocOperationExtractor->canExtract($source, $type, $context));

        if (!$canBeExtract) {
            try {
                $this->phpDocOperationExtractor->extract($source, $type, $context);
                $this->fail('should throw a exception of type [Draw\Swagger\Extraction\ExtractionImpossibleException]');
            } catch (ExtractionImpossibleException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function testExtract()
    {
        $this->phpDocOperationExtractor->registerExceptionResponseCodes('Draw\Swagger\Extraction\ExtractionImpossibleException', 400);
        $this->phpDocOperationExtractor->registerExceptionResponseCodes('LengthException', 408, 'Define message');

        $context = $this->extractStubServiceMethod('operation');

        $this->assertJsonStringEqualsJsonString(
            file_get_contents(__DIR__ . '/fixture/phpDocOperationExtractorExtract.json'),
            $context->getSwagger()->dump($context->getRootSchema(), false)
        );
    }

    public function testExtract_void()
    {
        $context = $this->extractStubServiceMethod('void');

        $this->assertJsonStringEqualsJsonString(
            file_get_contents(__DIR__ . '/fixture/phpDocOperationExtractorExtract_testExtract_void.json'),
            $context->getSwagger()->dump($context->getRootSchema(), false)
        );
    }

    public function testExtract_defaultVoid()
    {
        $context = $this->extractStubServiceMethod('defaultVoid');

        $this->assertJsonStringEqualsJsonString(
            file_get_contents(__DIR__ . '/fixture/phpDocOperationExtractorExtract_testExtract_defaultVoid.json'),
            $context->getSwagger()->dump($context->getRootSchema(), false)
        );
    }

    public function testExtract_arrayOfPrimitive()
    {
        $context = $this->extractStubServiceMethod('arrayOfPrimitive');

        $this->assertJsonStringEqualsJsonString(
            file_get_contents(__DIR__ . '/fixture/phpDocOperationExtractorExtract_testExtract_arrayOfPrimitive.json'),
            $context->getSwagger()->dump($context->getRootSchema(), false)
        );
    }

    public function testExtract_genericCollection()
    {
        $context = $this->extractStubServiceMethod('genericCollection');

        $this->assertJsonStringEqualsJsonString(
            file_get_contents(__DIR__ . '/fixture/phpDocOperationExtractorExtract_testExtract_genericCollection.json'),
            $context->getSwagger()->dump($context->getRootSchema(), false)
        );
    }

    /**
     * @param $method
     * @return ExtractionContext
     */
    private function extractStubServiceMethod($method)
    {
        $reflectionMethod = new ReflectionMethod(__NAMESPACE__ . '\PhpDocOperationExtractorStubService', $method);

        $context = $this->getExtractionContext();
        $context->getSwagger()->registerExtractor(new TypeSchemaExtractor());
        $schema = $context->getRootSchema();
        $schema->paths['/service'] = $pathItem = new PathItem();

        $pathItem->get = $operation = new Operation();

        $this->phpDocOperationExtractor->extract($reflectionMethod, $operation, $context);

        return $context;
    }

    public function getExtractionContext()
    {
        $swagger = new Swagger();
        $schema = $swagger->extract('{"swagger":"2.0","definitions":{}}');

        return new ExtractionContext($swagger, $schema);
    }
}

class PhpDocOperationExtractorStubClass
{

}

/**
 * This class is a stub and the code implementation make no sens, just the doc is usefull
 */
class PhpDocOperationExtractorStubService
{
    /**
     * @param PhpDocOperationExtractorStubService $service
     * @param $string
     * @param array $array
     *
     * @return PhpDocOperationExtractorStubService
     *
     * @throws Exception When problem occur
     * @throws LengthException
     * @throws ExtractionImpossibleException
     */
    public function operation(PhpDocOperationExtractorStubService $service, $string, array $array)
    {
        if($string) {
            throw new ExtractionImpossibleException();
        }
        return $service;
    }

    /**
     * @return void Does not return value
     */
    public function void()
    {

    }

    /**
     *
     */
    public function defaultVoid()
    {

    }

    /**
     * @return int[]
     */
    public function arrayOfPrimitive()
    {
        return [];
    }

    /**
     * @return PhpDocOperationExtractorStubClass<int>
     */
    public function genericCollection()
    {
        return new PhpDocOperationExtractorStubClass();
    }
}