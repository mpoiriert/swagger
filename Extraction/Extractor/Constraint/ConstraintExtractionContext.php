<?php

namespace Draw\Swagger\Extraction\Extractor\Constraint;

use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Schema\Schema;

class ConstraintExtractionContext
{
    /**
     * @var Schema
     */
    public $propertySchema;

    /**
     * @var Schema
     */
    public $classSchema;

    /**
     * class or property
     *
     * @var string
     */
    public $context;

    /**
     * @var string
     */
    public $propertyName;

    /**
     * @var ExtractionContextInterface
     */
    public $extractionContext;
}