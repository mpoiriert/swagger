<?php

namespace Draw\Swagger\Schema;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class PathParameter extends Parameter
{
    public function __construct()
    {
        $this->required = true;
    }
}