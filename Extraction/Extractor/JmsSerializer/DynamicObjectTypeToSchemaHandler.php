<?php namespace Draw\Swagger\Extraction\Extractor\JmsSerializer;

use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\Extractor\JmsExtractor;
use Draw\Swagger\Schema\Schema;
use JMS\Serializer\Metadata\PropertyMetadata;

class DynamicObjectTypeToSchemaHandler implements TypeToSchemaHandlerInterface
{
    public function extractSchemaFromType(
        PropertyMetadata $propertyMetadata,
        ExtractionContextInterface $extractionContext
    ) {
        if (!($type = $this->getDynamicObjectType($propertyMetadata))) {
            return null;
        }

        $propertySchema = new Schema();
        $propertySchema->type = 'object';
        $propertySchema->additionalProperties = new Schema();
        $propertySchema->additionalProperties->type = $type;

        return $propertySchema;
    }

    private function getDynamicObjectType(PropertyMetadata $item)
    {
        switch (true) {
            case !isset($item->type['name']):
            case !in_array($item->type['name'], array('array', 'ArrayCollection')):
            case !isset($item->type['params'][0]['name']):
            case isset($item->type['params'][0]['name']) != 'string':
            case !isset($item->type['params'][1]['name']):
                return null;

        }

        return $item->type['params'][1]['name'];
    }
}