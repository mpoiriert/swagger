<?php

namespace Draw\Swagger\Schema;

use Doctrine\Common\Annotations\Annotation\Required;
use JMS\Serializer\Annotation as JMS;

/**
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 *
 * @Annotation
 */
class Info
{
    /**
     * The title of the application.
     *
     * @var string
     *
     * @Required()
     * @JMS\Type("string")
     */
    public $title;

    /**
     * A short description of the application. GFM syntax can be used for rich text representation.
     *
     * @see https://help.github.com/articles/github-flavored-markdown/
     *
     * @var string
     *
     * @JMS\Type("string")
     */
    public $description;

    /**
     * The Terms of Service for the API.
     *
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\SerializedName("termsOfService")
     */
    public $termsOfService;

    /**
     * The contact information for the exposed API.
     *
     * @var Contact
     *
     * @JMS\Type("Draw\Swagger\Schema\Contact")
     */
    public $contact;

    /**
     * The license information for the exposed API.
     *
     * @var License
     *
     * @JMS\Type("Draw\Swagger\Schema\License")
     */
    public $license;

    /**
     * Provides the version of the application API (not to be confused by the specification version).
     *
     * @var string
     *
     * @Required()
     * @JMS\Type("string")
     */
    public $version;
} 