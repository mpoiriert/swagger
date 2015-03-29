<?php

namespace Draw\Swagger\Computed;

use Draw\Swagger\Schema\Operation as OperationSchema;
use Draw\Swagger\Schema\PathItem;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 */
class Operation 
{
    public $path;
    public $method;
    public $schema;
    public $pathItem;

    public function __construct($path, PathItem $pathItem, $method, OperationSchema $operation)
    {
        $this->pathItem = $pathItem;
        $this->path = $path;
        $this->method = $method;
        $this->schema = $operation;
    }
} 