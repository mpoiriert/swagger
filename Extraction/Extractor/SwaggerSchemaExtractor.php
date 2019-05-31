<?php

namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\Swagger;
use JMS\Serializer\SerializerInterface;

class SwaggerSchemaExtractor implements ExtractorInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
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
        if (!is_string($source)) {
            return false;
        }

        if (!is_object($type)) {
            return false;
        }

        if (!$type instanceof Swagger) {
            return false;
        }

        $schema = json_decode($source, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return false;
        }

        if (!array_key_exists('swagger', $schema)) {
            return false;
        }

        if ($schema['swagger'] != '2.0') {
            return false;
        }

        return true;
    }

    /**
     * Extract the requested data.
     *
     * The system is a incrementing extraction system. A extractor can be call before you and you must complete the
     * extraction.
     *
     * @param string $source
     * @param Swagger $swagger
     * @param ExtractionContextInterface $extractionContext
     */
    public function extract($source, $swagger, ExtractionContextInterface $extractionContext)
    {
        if (!$this->canExtract($source, $swagger, $extractionContext)) {
            throw new ExtractionImpossibleException();
        }

        $result = $this->serializer->deserialize($source, get_class($swagger), 'json');

        foreach ($result as $key => $value) {
            $swagger->{$key} = $value;
        }
    }
}