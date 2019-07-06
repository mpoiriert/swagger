<?php namespace Draw\Swagger\Extraction\Extractor\JmsSerializer;

use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Schema\Schema;
use JMS\Serializer\Metadata\PropertyMetadata;

interface TypeToSchemaHandlerInterface
{
    /**
     * @param ExtractionContextInterface $extractionContext
     * @param PropertyMetadata $propertyMetadata
     * @return Schema|null
     */
    public function extractSchemaFromType(
        PropertyMetadata $propertyMetadata,
        ExtractionContextInterface $extractionContext
    );
}