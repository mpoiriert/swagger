<?php

namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContext;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\Schema;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Exclusion\VersionExclusionStrategy;
use JMS\Serializer\Metadata\VirtualPropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\SerializationContext;
use Metadata\MetadataFactoryInterface;
use Metadata\PropertyMetadata;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;

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
     * Constructor, requires JMS Metadata factory
     */
    public function __construct(
        MetadataFactoryInterface $factory,
        PropertyNamingStrategyInterface $namingStrategy
    ) {
        $this->factory = $factory;
        $this->namingStrategy = $namingStrategy;
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

        if($extractionContext->getParameter(self::CONTEXT_PARAMETER_ENABLE_VERSION_EXCLUSION_STRATEGY)) {
            $info = $extractionContext->getRootSchema()->info;
            if(!isset($info->version)) {
                throw new \RuntimeException(
                    'You must specify the [swagger.info.version] if you activate jms version exclusion strategy.'
                );
            }
            $exclusionStrategies[] = new VersionExclusionStrategy($extractionContext->getRootSchema()->info->version);
        }

        foreach ($meta->propertyMetadata as $property => $item) {
            if ($this->shouldSkipProperty($exclusionStrategies, $item)) {
                continue;
            }

            if($this->isDynamicObject($item)) {
                $propertySchema = new Schema();
                $propertySchema->type = 'object';
                $propertySchema->additionalProperties = new Schema();
                $propertySchema->additionalProperties->type = $this->getNestedTypeInArray($item);
            } elseif ($type = $this->getNestedTypeInArray($item)) {
                $propertySchema = new Schema();
                $propertySchema->type = 'array';
                $propertySchema->items = $this->extractTypeSchema($type, $subContext);
            } else {
                $propertySchema = $this->extractTypeSchema($item->type['name'], $subContext);
            }

            if ($item->readOnly) {
                $propertySchema->readOnly = true;
            }

            $name = $this->namingStrategy->translateName($item);
            $schema->properties[$name] = $propertySchema;
            $propertySchema->description = (string)$this->getDescription($item) ?: null;
        }
    }

    private function extractTypeSchema($type, ExtractionContextInterface $extractionContext)
    {
        $extractionContext->getSwagger()->extract($type, $schema = new Schema(), $extractionContext);

        return $schema;
    }

    /**
     * Check the various ways JMS describes values in arrays, and
     * get the value type in the array
     *
     * @param  PropertyMetadata $item
     * @return string|null
     */
    private function getNestedTypeInArray(PropertyMetadata $item)
    {
        if (isset($item->type['name']) && in_array($item->type['name'], array('array', 'ArrayCollection'))) {
            if (isset($item->type['params'][1]['name'])) {
                // E.g. array<string, MyNamespaceMyObject>
                return $item->type['params'][1]['name'];
            }
            if (isset($item->type['params'][0]['name'])) {
                // E.g. array<MyNamespaceMyObject>
                return $item->type['params'][0]['name'];
            }
        }

        return null;
    }

    private function isDynamicObject(PropertyMetadata $item)
    {
        if (isset($item->type['name']) && in_array($item->type['name'], array('array', 'ArrayCollection'))) {
            if (isset($item->type['params'][1]['name']) && $item->type['params'][1]['name'] == 'string') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PropertyMetadata $item
     * @return string
     */
    private function getDescription(PropertyMetadata $item)
    {
        $factory = DocBlockFactory::createInstance();

        $ref = new \ReflectionClass($item->class);
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
        } catch (\ReflectionException $e) {
            return '';
        }

        return $docBlock->getSummary();
    }

    /**
     * @param \JMS\Serializer\Exclusion\ExclusionStrategyInterface[] $exclusionStrategies
     * @param $item
     * @return bool
     */
    private function shouldSkipProperty($exclusionStrategies, $item)
    {
        foreach ($exclusionStrategies as $strategy) {
            if (true === $strategy->shouldSkipProperty($item, SerializationContext::create())) {
                return true;
            }
        }

        return false;
    }
}