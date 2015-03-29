<?php

namespace Draw\Swagger\Schema;

use JMS\Serializer\Annotation as JMS;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 */
class Response
{
    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $description;

    /**
     * @var Schema
     *
     * @JMS\Type("Draw\Swagger\Schema\Schema")
     */
    public $schema;

    /**
     * @var Header[]
     *
     * @JMS\Type("array<string,Draw\Swagger\Schema\Header>")
     */
    public $headers;
} 