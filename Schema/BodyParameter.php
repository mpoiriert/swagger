<?php

namespace Draw\Swagger\Schema;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 */
class BodyParameter extends BaseParameter
{
    /**
     * The schema defining the type used for the body parameter.
     *
     * @var Schema
     *
     * @Assert\NotNull()
     * @Assert\Valid()
     * @JMS\Type("Draw\Swagger\Schema\Schema")
     */
    public $schema;

    public function __construct()
    {
        $this->name = "body";
    }
} 