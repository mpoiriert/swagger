<?php

namespace Draw\Swagger\Schema;

use JMS\Serializer\Annotation as JMS;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 *
 */
class Schema
{
    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $format;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $title;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $description;

    /**
     * @var Mixed
     *
     * @JMS\Type("Draw\Swagger\Schema\Mixed")
     */
    public $default;

    /**
     * @var number
     *
     * @JMS\Type("double")
     */
    public $maximum;

    /**
     * @var boolean
     *
     * @JMS\Type("boolean")
     */
    public $exclusiveMaximum;

    /**
     * @var number
     *
     * @JMS\Type("double")
     */
    public $minimum;

    /**
     * @var boolean
     *
     * @JMS\Type("boolean")
     * @JMS\SerializedName("exclusiveMinimum")
     */
    public $exclusiveMinimum;

    /**
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("maxLength")
     */
    public $maxLength;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $pattern;

    /**
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("maxItems")
     */
    public $maxItems;

    /**
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("minItems")
     */
    public $minItems;

    /**
     * @var boolean
     *
     * @JMS\Type("boolean")
     * @JMS\SerializedName("uniqueItems")
     */
    public $uniqueItems;

    /**
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("maxProperties")
     */
    public $maxProperties;

    /**
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("minProperties")
     */
    public $minProperties;

    /**
     * @var string[]
     *
     * @JMS\Type("array<string>")
     */
    public $required;

    /**
     * @var Mixed[]
     *
     * @JMS\Type("array<Draw\Swagger\Schema\Mixed>")
     */
    public $enum;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    public $type;

    /**
     * @var Schema
     *
     * @JMS\Type("Draw\Swagger\Schema\Schema")
     */
    public $items;

    /**
     * @var Schema[]
     *
     * @JMS\Type("array<Draw\Swagger\Schema\Schema>")
     * @JMS\SerializedName("allOf")
     */
    public $allOf;

    /**
     * @var Schema[]
     *
     * @JMS\Type("array<string,Draw\Swagger\Schema\Schema>")
     */
    public $properties;

    /**
     * @var Schema
     *
     * @JMS\Type("Draw\Swagger\Schema\Schema")
     * @JMS\SerializedName("additionalProperties")
     */
    public $additionalProperties;

    /**
     * This MAY be used only on properties schemas.
     * It has no effect on root schemas.
     * Adds Additional metadata to describe the XML representation format of this property.
     *
     * @var Xml
     *
     * @JMS\Type("Draw\Swagger\Schema\Xml")
     */
    public $xml;

    /**
     * Additional external documentation.
     *
     * @var ExternalDocumentation
     *
     * @JMS\Type("Draw\Swagger\Schema\ExternalDocumentation")
     * @JMS\SerializedName("externalDocs")
     */
    public $externalDocs;


    /**
     * A free-form property to include a an example of an instance for this schema.
     *
     * @var Mixed
     * @JMS\Type("Draw\Swagger\Schema\Mixed")
     */
    public $example;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\SerializedName("$ref")
     */
    public $ref;
} 