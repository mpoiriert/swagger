<?php

namespace Draw\Swagger\Schema;

use JMS\Serializer\Annotation as JMS;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 */
class SecurityScheme
{
    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $type;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $description;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $name;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $in;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $flow;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\SerializedName("authorizationUrl")
     */
    public $authorizationUrl;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\SerializedName("tokenUrl")
     */
    public $tokenUrl;

    /**
     * @var string[]
     *
     * @JMS\Type("array<string,string>")
     */
    public $scopes;
} 