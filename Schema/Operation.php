<?php

namespace Draw\Swagger\Schema;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 *
 * @Annotation
 */
class Operation
{
    /**
     * A list of tags for API documentation control.
     * Tags can be used for logical grouping of operations by resources or any other qualifier.
     *
     * @var string[]
     *
     * @JMS\Type("array<string>")
     */
    public $tags;

    /**
     * A short summary of what the operation does.
     * For maximum readability in the swagger-ui, this field SHOULD be less than 120 characters.
     *
     * @var string
     *
     * @JMS\Type("string")
     */
    public $summary;

    /**
     * A verbose explanation of the operation behavior. GFM syntax can be used for rich text representation.
     *
     * @see https://help.github.com/articles/github-flavored-markdown/
     *
     * @var string
     *
     * @JMS\Type("string")
     */
    public $description;

    /**
     * Additional external documentation for this operation.
     *
     * @var ExternalDocumentation
     *
     * @JMS\Type("Draw\Swagger\Schema\ExternalDocumentation")
     */
    public $externalDocs;

    /**
     * A friendly name for the operation.
     * The id MUST be unique among all operations described in the API.
     * Tools and libraries MAY use the operation id to uniquely identify an operation.
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("operationId")
     */
    public $operationId;

    /**
     * A list of MIME types the operation can consume.
     * This overrides the [consumes](#swaggerConsumes) definition at the Swagger Object.
     * An empty value MAY be used to clear the global definition. Value MUST be as described under Mime Types.
     *
     * @var string[]
     *
     * @JMS\Type("array<string>")
     */
    public $consumes;

    /**
     * A list of MIME types the operation can produce.
     * This overrides the [produces](#swaggerProduces) definition at the Swagger Object.
     * An empty value MAY be used to clear the global definition. Value MUST be as described under Mime Types.
     *
     * @var string[]
     *
     * @JMS\Type("array<string>")
     */
    public $produces;

    /**
     * A list of parameters that are applicable for this operation.
     * If a parameter is already defined at the Path Item, the new definition will override it, but can never remove it.
     * The list MUST NOT include duplicated parameters.
     * A unique parameter is defined by a combination of a name and location.
     * The list can use the Reference Object to link to parameters that are defined at the Swagger Object's parameters.
     * There can be one "body" parameter at most.
     *
     * @var Parameter[]
     *
     * @JMS\Type("array<Draw\Swagger\Schema\BaseParameter>")
     */
    public $parameters;

    /**
     * The list of possible responses as they are returned from executing this operation.
     *
     * @var Response[]
     *
     * @Assert\NotBlank()
     * @JMS\Type("array<string,Draw\Swagger\Schema\Response>")
     */
    public $responses;

    /**
     * The transfer protocol for the operation. Values MUST be from the list: "http", "https", "ws", "wss". The value overrides the Swagger Object schemes definition.
     *
     * @var string[]
     *
     * @Assert\Choice({"http","https","ws","wss"})
     * @JMS\Type("string")
     */
    public $schemes;

    /**
     * Declares this operation to be deprecated.
     * Usage of the declared operation should be refrained.
     * Default value is false.
     *
     * @var boolean
     *
     * @JMS\Type("boolean")
     */
    public $deprecated;

    /**
     * A declaration of which security schemes are applied for this operation.
     * The list of values describes alternative security schemes that can be used
     * (that is, there is a logical OR between the security requirements).
     * This definition overrides any declared top-level security.
     * To remove a top-level security declaration, an empty array can be used.
     *
     * @var SecurityRequirement[]
     *
     * @JMS\Type("array<Draw\Swagger\Schema\SecurityRequirement>")
     */
    public $security;
} 