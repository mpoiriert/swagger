<?php namespace Draw\Swagger\Tests\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\Extractor\SwaggerSchemaExtractor;
use Draw\Swagger\Schema\Swagger;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;

class SwaggerSchemaExtractorTest extends TestCase
{
    public function provideTestCanExtract()
    {
        return array(
            array(array(), new Swagger(), false),
            array('toto', new Swagger(), false),
            array("{}", new Swagger(), false),
            array('{"swagger":"1.0"}', new Swagger(), false),
            array('{"swagger":"2.0"}', '', false),
            array('{"swagger":"2.0"}', new stdClass(), false)
        );
    }

    /**
     * @dataProvider provideTestCanExtract
     *
     * @param $source
     * @param $type
     * @param $expected
     */
    public function testCanExtract($source, $type, $expected)
    {
        $extractor = new SwaggerSchemaExtractor(SerializerBuilder::create()->build());

        /** @var ExtractionContextInterface $context */
        $context = $this->getMockForAbstractClass(ExtractionContextInterface::class);

        $this->assertSame($expected, $extractor->canExtract($source, $type, $context));

        if($expected) {
            $extractor->extract($source, $type, $context);
            $this->assertTrue(true);
        } else {
            try {
                $extractor->extract($source, $type, $context);
                $this->fail('should throw a exception of type [Draw\Swagger\Extraction\ExtractionImpossibleException]');
            } catch(ExtractionImpossibleException $e) {
                $this->assertTrue(true);
            }
        }
    }
}