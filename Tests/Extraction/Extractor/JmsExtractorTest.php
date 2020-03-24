<?php namespace Draw\Swagger\Tests\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContext;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\Extractor\JmsExtractor;
use Draw\Swagger\Extraction\Extractor\TypeSchemaExtractor;
use Draw\Swagger\Schema\Schema;
use Draw\Swagger\Swagger;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class JmsExtractorTest extends TestCase
{
    /**
     * @var JmsExtractor
     */
    private $jmsExtractor;

    public function provideTestCanExtract()
    {
        return array(
            array(null, null, false),
            array(null, new Schema(), false),
            array(__NAMESPACE__ . '\JmsExtractorStubModel', null, false),
            array(__NAMESPACE__ . '\JmsExtractorStubModel', new Schema(), true),
        );
    }

    public function setUp()
    {
        $serializer = SerializerBuilder::create()->build();

        //This is to be compatible for version 1,2,3 of jms since the method getMetadataFactory doesn't exists anymore
        //When using the library you should inject the factory on your initializing flow
        $property = new \ReflectionProperty(get_class($serializer), 'factory');
        $property->setAccessible(true);
        $metadataFactory = $property->getValue($serializer);

        $this->jmsExtractor = new JmsExtractor(
            $metadataFactory,
            new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy())
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
        if (!is_null($source)) {
            $source = new \ReflectionClass($source);
        }

        /** @var ExtractionContextInterface $context */
        $context = $this->getMockForAbstractClass(ExtractionContextInterface::class);

        $this->assertSame($canBeExtract, $this->jmsExtractor->canExtract($source, $type, $context));

        if (!$canBeExtract) {
            try {
                $this->jmsExtractor->extract($source, $type, $context);
                $this->fail('should throw a exception of type [Draw\Swagger\Extraction\ExtractionImpossibleException]');
            } catch (ExtractionImpossibleException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function testExtract()
    {
        $reflectionClass = new ReflectionClass(__NAMESPACE__ . '\JmsExtractorStubModel');

        $context = $this->getExtractionContext();

        //Need to be there to validate that JMS extract it's type properly
        $swagger = $context->getSwagger();
        $swagger->registerExtractor(new TypeSchemaExtractor());
        $swagger->registerExtractor($this->jmsExtractor);

        $context->setParameter('model-context', ['serializer-groups' => ['test']]);
        $schema = $context->getRootSchema();

        $schema->addDefinition($reflectionClass->getName(), $modelSchema = new Schema());

        $this->jmsExtractor->extract($reflectionClass, $modelSchema, $context);

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
     * @var
     *
     * @JMS\Type("Draw\Swagger\Tests\Extraction\Extractor\JmsExtractorStubGeneric<string>")
     * @JMS\Groups("test")
     * @JMS\ReadOnly()
     */
    public $generic;

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
     * @JMS\Type("array<Draw\Swagger\Tests\Extraction\Extractor\JmsExtractorStubModel>")
     * @JMS\Groups("test")
     */
    public $array;

    /**
     * The array
     *
     * @var array
     * @JMS\Type("array<Draw\Swagger\Tests\Extraction\Extractor\JmsExtractorStubModel>")
     */
    public $notThereByGroup;

    /**
     * @var string
     * @JMS\Exclude()
     * @JMS\Groups("test")
     */
    public $notThere;

    /**
     * The virtual property.
     *
     * @JMS\VirtualProperty()
     * @JMS\Type("Draw\Swagger\Tests\Extraction\Extractor\JmsExtractorStubModel")
     * @JMS\Groups("test")
     */
    public function getVirtual()
    {
    }
}

class JmsExtractorStubGeneric
{
    /**
     * The generic property.
     *
     * @var string
     * @JMS\Type("generic")
     * @JMS\Groups("test")
     * @JMS\ReadOnly()
     */
    public $name;
}