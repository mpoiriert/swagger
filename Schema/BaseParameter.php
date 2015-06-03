<?php

namespace Draw\Swagger\Schema;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 *
 * @see https://github.com/swagger-api/swagger-spec/blob/master/versions/2.0.md#parameterObject
 *
 * @Annotation
 *
 * @JMS\Discriminator(
 *      field="in",
 *      map={
 *          "body":"Draw\Swagger\Schema\BodyParameter",
 *          "header":"Draw\Swagger\Schema\HeaderParameter",
 *          "path":"Draw\Swagger\Schema\PathParameter",
 *          "query":"Draw\Swagger\Schema\QueryParameter",
 *          "formData":"Draw\Swagger\Schema\FormDataParameter",
 *          "other": "Draw\Swagger\Schema\Parameter"
 *      }
 * )
 */
abstract class BaseParameter
{
    /**
     * The name of the parameter. Parameter names are case sensitive.
     *  - If in is "path", the name field MUST correspond to the associated path segment from the path field in the Paths Object.
     *    See Path Templating for further information.
     *
     *  - For all other cases, the name corresponds to the parameter name used based on the in property.
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @JMS\Type("string")
     */
    public $name;

    /**
     * A brief description of the parameter. This could contain examples of use.
     * GFM syntax can be used for rich text representation.
     *
     * @var string
     *
     * @JMS\Type("string")
     */
    public $description;

    /**
     * Determines whether this parameter is mandatory.
     * If the parameter is in "path", this property is required and its value MUST be true.
     * Otherwise, the property MAY be included and its default value is false.
     *
     * @var boolean
     *
     * @JMS\Type("boolean")
     */
    public $required;

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("in")
     */
    public function getType()
    {
        $striped = str_replace(
            array(__NAMESPACE__ . '\\','Parameter'),
            array('',''),
            get_class($this)
        );

        return lcfirst($striped);
    }
} 