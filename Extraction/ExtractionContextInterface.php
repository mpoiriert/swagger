<?php

namespace Draw\Swagger\Extraction;

use Draw\Swagger\Swagger;

interface ExtractionContextInterface
{
    public function getSwagger(): Swagger;

    public function getRootSchema(): \Draw\Swagger\Schema\Swagger;

    public function hasParameter($name): bool;

    public function getParameter($name, $default = null);

    public function getParameters(): array;

    public function setParameter($name, $value): void;

    public function removeParameter($name): void;

    public function setParameters(array $parameters): void;
    
    public function createSubContext(): ExtractionContextInterface;
}