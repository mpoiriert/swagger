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

    public function getRootSchema(): Schema
    {
        return $this->rootSchema;
    }

    public function getSwagger(): Swagger
    {
        return $this->swagger;
    }

    public function hasParameter($name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function getParameter($name, $default = null)
    {
        return $this->hasParameter($name) ? $this->parameters[$name] : $default;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameter($name, $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function removeParameter($name): void
    {
        unset($this->parameters[$name]);
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function createSubContext(): ExtractionContextInterface
    {
        return clone $this;
    }
}