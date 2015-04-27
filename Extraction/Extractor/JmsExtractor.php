<?php

namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContext;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\Schema;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Metadata\VirtualPropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\SerializationContext;
use Metadata\MetadataFactoryInterface;
use Metadata\PropertyMetadata;
use phpDocumentor\Reflection\DocBlock;
use ReflectionClass;

class JmsExtractor implements ExtractorInterface
{
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

        if ($groups = $extractionContext->getParameter('jms-groups', array())) {
            $exclusionStrategies[] = new GroupsExclusionStrategy($groups);
        }

        foreach ($meta->propertyMetadata as $property => $item) {
            if ($this->shouldSkipProperty($exclusionStrategies, $item)) {
                continue;
            }

            if ($type = $this->getNestedTypeInArray($item)) {
                $propertySchema = new Schema();
                $propertySchema->type = 'array';
                $propertySchema->items = $this->extractTypeSchema($type, $extractionContext);
            } else {
                $propertySchema = $this->extractTypeSchema($item->type['name'], $extractionContext);
            }

            if ($item->readOnly) {
                $propertySchema->readOnly = true;
            }

            $name = $this->namingStrategy->translateName($item);
            $schema->properties[$name] = $propertySchema;
            $propertySchema->description = $this->getDescription($item);
        }
    }

    private function extractTypeSchema($type, ExtractionContext $extractionContext)
    {
        $schema = new Schema();

        if ($this->isPrimitive($type)) {
            $schema->type = $type;
        } else {
            $rootSchema = $extractionContext->getRootSchema();
            if (!$rootSchema->hasDefinition($type)) {
                $rootSchema->addDefinition($type, $definitionSchema = new Schema());
                $extractionContext->getSwagger()->extract(
                    new ReflectionClass($type),
                    $definitionSchema,
                    $extractionContext
                );
            }

            $schema->ref = $rootSchema->getDefinitionReference($type);
        }

        return $schema;
    }

    private function isPrimitive($type)
    {
        return in_array($type, array('boolean', 'integer', 'string', 'float', 'double', 'array', 'DateTime', null));
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

    /**
     * @param PropertyMetadata $item
     * @return string
     */
    private function getDescription(PropertyMetadata $item)
    {
        $ref = new \ReflectionClass($item->class);
        if ($item instanceof VirtualPropertyMetadata) {
            try {
                $docBlock = new DocBlock($ref->getMethod($item->getter)->getDocComment());
            } catch (\ReflectionException $e) {
                return '';
            }
        } else {
            $docBlock = new DocBlock($ref->getProperty($item->name)->getDocComment());
        }

        return $docBlock->getShortDescription();
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