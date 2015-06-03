<?php

namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Schema\Schema as SupportedTarget;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\Schema;

class TypeSchemaExtractor implements ExtractorInterface
{
    /**
     * Return if the extractor can extract the requested data or not.
     *
     * @param $source
     * @param SupportedTarget $target
     * @param ExtractionContextInterface $extractionContext
     * @return boolean
     */
    public function canExtract($source, $target, ExtractionContextInterface $extractionContext)
    {
        if (!$target instanceof SupportedTarget) {
            return false;
        }

        if(is_null($this->getPrimitiveType($source))) {
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
     * @param SupportedTarget $target
     * @param ExtractionContextInterface $extractionContext
     */
    public function extract($source, $target, ExtractionContextInterface $extractionContext)
    {
        if (!$this->canExtract($source, $target, $extractionContext)) {
            throw new ExtractionImpossibleException();
        }

        $primitiveType = $this->getPrimitiveType($source);

        $target->type = $primitiveType['type'];

        if($target->type == 'array') {
            $target->items = $itemsSchema = new Schema();
            $extractionContext->getSwagger()->extract(
                $primitiveType['subType'],
                $itemsSchema,
                $extractionContext
            );
            return;
        }

        if($target->type == "object") {
            $reflectionClass = new \ReflectionClass($primitiveType['class']);
            $extractionContext->getSwagger()->extract(
                $reflectionClass,
                $target,
                $extractionContext
            );
            return;
        }

        if(isset($primitiveType['format'])) {
            $target->format = $primitiveType['format'];
        }
    }

    private function getPrimitiveType($type)
    {
        if(!is_string($type)) {
            return null;
        }

        $primitiveType = array();

        $typeOfArray = str_replace('[]','', $type);
        if($typeOfArray != $type) {
            if($typeOfArray !== substr($type,0,-2)) {
                return null;
            }

            $primitiveType['type'] = 'array';
            $primitiveType['subType'] = $typeOfArray;
            return $primitiveType;
        }

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

        if(class_exists($type)) {
            return array(
                'type' => 'object',
                'class' => $type
            );
        };

        return null;
    }
}