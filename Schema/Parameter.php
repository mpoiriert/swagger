<?php

namespace Draw\Swagger\Schema;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 */
class Parameter extends BaseParameter
{
    /**
     * The type of the parameter. Since the parameter is not located at the request body, it is limited to simple types
     * (that is, not an object). The value MUST be one of "string", "number", "integer", "boolean", "array" or "file".
     * If type is "file", the consumes MUST be either "multipart/form-data" or " application/x-www-form-urlencoded"
     * and the parameter MUST be in "formData".
     *
     * @var string
     *
     * @Assert\Choice({"string", "number", "integer", "boolean", "array", "file"})
     * @JMS\Type("string")
     */
    public $type;

    /**
     * The extending format for the previously mentioned type. See Data Type Formats for further details.
     *
     * @var string
     * @JMS\Type("string")
     */
    public $format;

    /**
     * Required if type is "array". Describes the type of items in the array.
     *
     * @var Items
     *
     * @JMS\Type("Draw\Swagger\Schema\Items")
     */
    public $items;

    /**
     * Determines the format of the array if type array is used. Possible values are:
     *   csv - comma separated values foo,bar.
     *   ssv - space separated values foo bar.
     *   tsv - tab separated values foo\tbar.
     *   pipes - pipe separated values foo|bar.
     *   multi - corresponds to multiple parameter instances instead of multiple values for a single instance foo=bar&foo=baz.
     *           This is valid only for parameters in "query" or "formData".
     *
     *   Default value is csv.
     *
     * @var string
     *
     * @Assert\Choice({"csv", "ssv", "tsv", "pipes", "multi"})
     * @JMS\Type("string")
     * @JMS\SerializedName("collectionFormat")
     */
    public $collectionFormat;

    /**
     * Sets a default value to the parameter. The type of the value depends on the defined type.
     *
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor101
     *
     * @var Mixed
     *
     * @JMS\Type("Draw\Swagger\Schema\Mixed")
     */
    public $default;

    /**
     * @see  http://json-schema.org/latest/json-schema-validation.html#anchor17
     *
     * @var integer
     *
     * @JMS\Type("integer")
     */
    public $maximum;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor17
     *
     * @var boolean
     *
     * @JMS\Type("boolean")
     * @JMS\SerializedName("exclusiveMaximum")
     */
    public $exclusiveMaximum;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor21
     *
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("minimum")
     */
    public $minimum;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor21
     *
     * @var boolean
     *
     * @JMS\Type("boolean")
     * @JMS\SerializedName("exclusiveMinimum")
     */
    public $exclusiveMinimum;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor26
     *
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("maxLength")
     */
    public $maxLength;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor29
     *
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("minLength")
     */
    public $minLength;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor33
     *
     * @var string
     *
     * @JMS\Type("string")
     */
    public $pattern;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor42
     *
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("maxItems")
     */
    public $maxItems;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor45
     *
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("minItems")
     */
    public $minItems;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor49
     *
     * @var boolean
     *
     * @JMS\Type("boolean")
     * @JMS\SerializedName("uniqueItems")
     */
    public $uniqueItems;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor76
     *
     * @var Mixed[]
     *
     * @JMS\Type("array<Draw\Swagger\Schema\Mixed>")
     */
    public $enum;

    /**
     * @see http://json-schema.org/latest/json-schema-validation.html#anchor14
     *
     * @var integer
     *
     * @JMS\Type("integer")
     * @JMS\SerializedName("multipleOf")
     */
    public $multipleOf;

    /**
     * @JMS\PreSerialize()
     */
    public function preSerialize()
    {
        $this->default = Mixed::convert($this->default);
        $this->enum = Mixed::convert($this->enum, true);
    }
} 