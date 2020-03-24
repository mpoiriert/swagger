<?php namespace Draw\Swagger\Extraction\Extractor\JmsSerializer;

use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Schema\Schema;
use JMS\Serializer\Metadata\PropertyMetadata;

class GenericTypeToSchemaHandler implements TypeToSchemaHandlerInterface
{
    public function extractSchemaFromType(
        PropertyMetadata $propertyMetadata,
        ExtractionContextInterface $extractionContext
    ) {
        if ($genericType = $this->getGenericType($propertyMetadata)) {
            $extractionContext->getSwagger()
                ->extract(
                    $propertyMetadata->type['name'] . '<' . $genericType . '>',
                    $propertySchema = new Schema(),
                    $extractionContext
                );
            return $propertySchema;
        }

        if(($propertyMetadata->type['name'] ?? null) != 'generic') {
            return null;
        }

        $genericTemplate = $extractionContext->getParameter('generic-template');

        if(!$genericTemplate) {
            return null;
        }

        $extractionContext->getSwagger()
            ->extract(
                $genericTemplate,
                $propertySchema = new Schema(),
                $extractionContext
            );

        return $propertySchema;
    }

    private function getGenericType(PropertyMetadata $item)
    {
        switch (true) {
            case !isset($item->type['name']):
            case in_array($item->type['name'], array('array', 'ArrayCollection')):
            case !class_exists($item->type['name']);
            case !isset($item->type['params'][0]['name']):
            case isset($item->type['params'][1]['name']):
                return null;
        }

        return $item->type['params'][0]['name'];
    }
}