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

        if(is_null($type)) {
            return $schema;
        }

        $primitiveType = $this->getPrimitiveType($type);

        if ($primitiveType['type'] != "object") {
            $schema->type = $primitiveType['type'];
            if(array_key_exists('format',$primitiveType)) {
                $schema->format = $primitiveType['format'];
            }
            return $schema;
        }

        $rootSchema = $extractionContext->getRootSchema();
        if (!$rootSchema->hasDefinition($type)) {
            $rootSchema->addDefinition($type, $definitionSchema = new Schema());
            $definitionSchema->type = "object";
            $extractionContext->getSwagger()->extract(
                new ReflectionClass($type),
                $definitionSchema,
                $extractionContext
            );
        }
        $schema->type = "object";
        $schema->ref = $rootSchema->getDefinitionReference($type);

        return $schema;
    }

    private function getPrimitiveType($type)
    {
        $types = array(
            'int' => array('type' => 'integer', 'format' => 'int32'),
            'integer' => array('type' => 'integer', 'format' => 'int32'),
            'long' => array('type' => 'integer', 'format' => 'int64'),
            'float' => array('type' => 'number', 'format' => 'float'),
            'double' => array('type' => 'number', 'format' => 'double'),
            'string' => array('type' => 'string'),
            'byte' => array('type' => 'string', 'format' => 'byte'),
            'boolean' => array('type' => 'boolean'),
            'date' => array('type' => 'string', 'format' => 'date'),
            'DateTime' => array('type' => 'string', 'format' => 'date-time'),
            'dateTime' => array('type' => 'string', 'format' => 'date-time'),
            'password' => array('type' => 'string', 'format' => 'password')
        );

        if(array_key_exists($type, $types)) {
            return $types[$type];
        }

        return array('type' => 'object');
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