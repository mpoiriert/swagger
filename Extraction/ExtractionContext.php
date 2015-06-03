<?php

namespace Draw\Swagger\Extraction;

use Draw\Swagger\Schema\Swagger as Schema;
use Draw\Swagger\Swagger;

class ExtractionContext implements ExtractionContextInterface
{
    /**
     * @var Schema
     */
    private $rootSchema;

    /**
     * @var Swagger
     */
    private $swagger;

    private $parameters = array();

    public function __construct(Swagger $swagger, Schema $rootSchema)
    {
        $this->rootSchema = $rootSchema;
        $this->swagger = $swagger;
    }

    public function getRootSchema()
    {
        return $this->rootSchema;
    }

    public function getSwagger()
    {
        return $this->swagger;
    }

    /**
     * @return mixed
     */
    public function hasParameter($name)
    {
        return array_key_exists($name, $this->parameters);
    }

    public function getParameter($name, $default = null)
    {
        return $this->hasParameter($name) ? $this->parameters[$name] : $default;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    public function removeParameter($name)
    {
        unset($this->parameters[$name]);
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function createSubContext()
    {
        return clone $this;
    }
}