<?php

namespace Draw\Swagger\Extraction;

interface ExtractionContextInterface
{
    /**
     * @return \Draw\Swagger\Swagger
     */
    public function getSwagger();

    /**
     * @return \Draw\Swagger\Schema\Swagger
     */
    public function getRootSchema();

    /**
     * @return boolean
     */
    public function hasParameter($name);

    /**
     * @param $name
     * @param null $default
     * @return mixed
     */
    public function getParameter($name, $default = null);

    /**
     * @return array
     */
    public function getParameters();

    /**
     * @param $name
     * @param $value
     */
    public function setParameter($name, $value);

    /**
     * @param $name
     */
    public function removeParameter($name);

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters);
}