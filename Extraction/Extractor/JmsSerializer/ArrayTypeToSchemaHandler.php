<?php namespace Draw\Swagger\Extraction\Extractor\JmsSerializer;

use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\Extractor\JmsExtractor;
use Draw\Swagger\Schema\Schema;
use JMS\Serializer\Metadata\PropertyMetadata;

class ArrayTypeToSchemaHandler implements TypeToSchemaHandlerInterface
{
    public function extractSchemaFromType(
        PropertyMetadata $propertyMetadata,
        ExtractionContextInterface $extractionContext
    ) {
        if (!($type = $this->getNestedTypeInArray($propertyMetadata))) {
            return null;
        }

        $propertySchema = new Schema();
        $propertySchema->type = 'array';
        $propertySchema->items = JmsExtractor::extractTypeSchema($type, $extractionContext, $propertyMetadata);

        return $propertySchema;
    }

    private function getNestedTypeInArray(PropertyMetadata $item)
    {
        if (isset($item->type['name']) && in_array($item->type['name'], array('array', 'ArrayCollection'))) {
            if (isset($item->type['params'][1]['name'])) {
                // E.g. array<integer, MyNamespaceMyObject>
                return $item->type['params'][1]['name'];
            }
            if (isset($item->type['params'][0]['name'])) {
                // E.g. array<MyNamespaceMyObject>
                return $item->type['params'][0]['name'];
            }
        }

        return null;
    }
}