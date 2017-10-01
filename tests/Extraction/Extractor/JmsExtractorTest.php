<?php

namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContext;
use Draw\Swagger\Swagger;
use JMS\Serializer\Annotation as JMS;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Schema\Schema;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;

class JmsExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function provideTestCanExtract()
    {
        return array(
            array(null, null, false),
            array(null, new Schema(), false),
            array(__NAMESPACE__ . '\JmsExtractorStubModel', null, false),
            array(__NAMESPACE__ . '\JmsExtractorStubModel', new Schema(), true),
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
        if (!is_null($source)) {
            $source = new \ReflectionClass($source);
        }
        $extractor = new JmsExtractor(
            SerializerBuilder::create()->build()->getMetadataFactory(),
            new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy())
        );

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
        $extractor = new JmsExtractor(
            SerializerBuilder::create()->build()->getMetadataFactory(),
            new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy())
        );
        $reflectionClass = new \ReflectionClass(__NAMESPACE__ . '\JmsExtractorStubModel');

        $context = $this->getExtractionContext();
        $context->setParameter('jms-groups', array('test'));
        $schema = $context->getRootSchema();

        $schema->addDefinition($reflectionClass->getName(), $modelSchema = new Schema());

        $extractor->extract($reflectionClass, $modelSchema, $context);

        $jsonSchema = $context->getSwagger()->dump($context->getRootSchema(), false);

        $this->assertJsonStringEqualsJsonString(
            file_get_contents(__DIR__ . '/fixture/jmsExtractorTestExtract.json'),
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

class JmsExtractorStubModel
{
    /**
     * The name
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups("test")
     * @JMS\ReadOnly()
     */
    public $name;

    /**
     * Serialized property
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("serializeProperty")
     * @JMS\Groups("test")
     */
    public $serializeProperty;

    /**
     * The array
     *
     * @var array
     * @JMS\Type("array<Draw\Swagger\Extraction\Extractor\JmsExtractorStubModel>")
     * @JMS\Groups("test")
     */
    public $array;

    /**
     * The array
     *
     * @var array
     * @JMS\Type("array<Draw\Swagger\Extraction\Extractor\JmsExtractorStubModel>")
     */
    public $notThereByGroup;

    /**
     * @var string
     */
    public $notThere;

    /**
     * The virtual property.
     *
     * @JMS\VirtualProperty()
     * @JMS\Type("Draw\Swagger\Extraction\Extractor\JmsExtractorStubModel")
     * @JMS\Groups("test")
     */
    public function getVirtual()
    {
    }
}