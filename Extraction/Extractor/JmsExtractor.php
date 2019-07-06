<?php namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\Extractor\JmsSerializer\ArrayTypeToSchemaHandler;
use Draw\Swagger\Extraction\Extractor\JmsSerializer\DynamicObjectTypeToSchemaHandler;
use Draw\Swagger\Extraction\Extractor\JmsSerializer\TypeToSchemaHandlerInterface;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\Schema;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Exclusion\VersionExclusionStrategy;
use JMS\Serializer\Metadata\VirtualPropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\SerializationContext;
use Metadata\MetadataFactoryInterface;
use JMS\Serializer\Metadata\PropertyMetadata;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class JmsExtractor implements ExtractorInterface
{
    const CONTEXT_PARAMETER_ENABLE_VERSION_EXCLUSION_STRATEGY = 'jms-enable-version-exclusion-strategy';

    /**
     * @var MetadataFactoryInterface
     */
    private $factory;

    /**
     * @var PropertyNamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @var array|TypeToSchemaHandlerInterface[]
     */
    private $typeToSchemaHandlers = [];

    public function __construct(
        MetadataFactoryInterface $factory,
        PropertyNamingStrategyInterface $namingStrategy
    ) {
        $this->factory = $factory;
        $this->namingStrategy = $namingStrategy;

        $this->registerTypeToSchemaHandler(new DynamicObjectTypeToSchemaHandler());
        $this->registerTypeToSchemaHandler(new ArrayTypeToSchemaHandler());
    }

    public function registerTypeToSchemaHandler(TypeToSchemaHandlerInterface $typeToSchemaHandler)
    {
        $this->typeToSchemaHandlers[] = $typeToSchemaHandler;
    }

    /**
     * Return if the extractor can extract the requested data or not.
     *
     * @param $source
     * @param $type
     * @param ExtractionContextInterface $extractionContext
     * @return boolean
     */
    public function canExtract($source, $type, ExtractionContextInterface $extractionContext)
    {
        if (!$source instanceof ReflectionClass) {
            return false;
        }

        if (!$type instanceof Schema) {
            return false;
        }

        return !is_null($this->factory->getMetadataForClass($source->getName()));
    }

    /**
     * Extract the requested data.
     *
     * The system is a incrementing extraction system. A extractor can be call before you and you must complete the
     * extraction.
     *
     * @param ReflectionClass $reflectionClass
     * @param Schema $schema
     * @param ExtractionContextInterface $extractionContext
     */
    public function extract($reflectionClass, $schema, ExtractionContextInterface $extractionContext)
    {
        if (!$this->canExtract($reflectionClass, $schema, $extractionContext)) {
            throw new ExtractionImpossibleException();
        }

        $meta = $this->factory->getMetadataForClass($reflectionClass->getName());

        $exclusionStrategies = array();

        $subContext = $extractionContext->createSubContext();

        $modelContext = $subContext->getParameter('model-context', []);

        if (isset($modelContext['serializer-groups'])) {
            $exclusionStrategies[] = new GroupsExclusionStrategy($modelContext['serializer-groups']);
        }

        if ($extractionContext->getParameter(self::CONTEXT_PARAMETER_ENABLE_VERSION_EXCLUSION_STRATEGY)) {
            $info = $extractionContext->getRootSchema()->info;
            if (!isset($info->version)) {
                throw new RuntimeException(
                    'You must specify the [swagger.info.version] if you activate jms version exclusion strategy.'
                );
            }
            $exclusionStrategies[] = new VersionExclusionStrategy($extractionContext->getRootSchema()->info->version);
        }

        /** @var PropertyMetadata $item */
        foreach ($meta->propertyMetadata as $item) {
            if ($this->shouldSkipProperty($exclusionStrategies, $item, $subContext)) {
                continue;
            }

            $propertySchema = null;
            foreach ($this->typeToSchemaHandlers as $typeToSchemaHandler) {
                if ($propertySchema = $typeToSchemaHandler->extractSchemaFromType($item, $subContext)) {
                    break;
                }
            }

            if (!$propertySchema) {
                $propertySchema = $this->extractTypeSchema($item->type['name'], $subContext, $item);
            }

            if ($item->readOnly) {
                $propertySchema->readOnly = true;
            }

            $name = $this->namingStrategy->translateName($item);
            $schema->properties[$name] = $propertySchema;
            $propertySchema->description = (string)$this->getDescription($item) ?: null;
        }
    }

    public static function extractTypeSchema(
        $type,
        ExtractionContextInterface $extractionContext,
        PropertyMetadata $propertyMetadata
    ) {
        $extractionContext = $extractionContext->createSubContext();
        $path = $extractionContext->getParameter('jms-path', []);
        $path[] = $propertyMetadata;
        $extractionContext->setParameter('jms-path', $path);
        $extractionContext->getSwagger()->extract($type, $schema = new Schema(), $extractionContext);

        return $schema;
    }

    /**
     * @param PropertyMetadata $item
     * @return string
     */
    private function getDescription(PropertyMetadata $item)
    {
        $factory = DocBlockFactory::createInstance();

        $ref = new ReflectionClass($item->class);
        try {
            if ($item instanceof VirtualPropertyMetadata) {
                $docBlock = $factory->create($ref->getMethod($item->getter)->getDocComment());
            } else {
                if ($docComment = $ref->getProperty($item->name)->getDocComment()) {
                    $docBlock = $factory->create($docComment);
                } else {
                    return '';
                }
            }
        } catch (ReflectionException $e) {
            return '';
        }

        return $docBlock->getSummary();
    }

    /**
     * @param ExclusionStrategyInterface[] $exclusionStrategies
     * @param PropertyMetadata $item
     * @param ExtractionContextInterface $extractionContext
     * @return bool
     */
    private function shouldSkipProperty(
        $exclusionStrategies,
        PropertyMetadata $item,
        ExtractionContextInterface $extractionContext
    ) {
        $serializationContext = SerializationContext::create();

        foreach ($extractionContext->getParameter('jms-path', []) as $metadata) {
            $serializationContext->getMetadataStack()->push($metadata);
        }

        foreach ($exclusionStrategies as $strategy) {
            if (true === $strategy->shouldSkipProperty($item, $serializationContext)) {
                return true;
            }
        }

        return false;
    }
}