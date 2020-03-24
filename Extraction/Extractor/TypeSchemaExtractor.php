<?php

namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Schema\Schema as SupportedTarget;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\Schema;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Collection;

class TypeSchemaExtractor implements ExtractorInterface
{
    /**
     * @var string[]
     */
    private $definitionAliases = array();

    private $definitionHashes = array();

    private static $typeResolver;

    public function registerDefinitionAlias($definition, $alias)
    {
        $this->definitionAliases[$definition] = $alias;
    }

    /**
     * Return if the extractor can extract the requested data or not.
     *
     * @param $source
     * @param SupportedTarget $target
     * @param ExtractionContextInterface $extractionContext
     * @return boolean
     */
    public function canExtract($source, $target, ExtractionContextInterface $extractionContext)
    {
        if (!$target instanceof SupportedTarget) {
            return false;
        }

        if (is_null(self::getPrimitiveType($source))) {
            return false;
        }

        return true;
    }

    /**
     * Extract the requested data.
     *
     * The system is a incrementing extraction system. A extractor can be call before you and you must complete the
     * extraction.
     *
     * @param string $source
     * @param SupportedTarget $target
     * @param ExtractionContextInterface $extractionContext
     *
     * @throws ExtractionImpossibleException
     */
    public function extract($source, $target, ExtractionContextInterface $extractionContext)
    {
        if (!$this->canExtract($source, $target, $extractionContext)) {
            throw new ExtractionImpossibleException();
        }

        $primitiveType = self::getPrimitiveType($source);

        $target->type = $primitiveType['type'];

        if ($target->type == 'array') {
            $target->items = $itemsSchema = new Schema();
            if (isset($primitiveType['subType'])) {
                $extractionContext->getSwagger()->extract(
                    $primitiveType['subType'],
                    $itemsSchema,
                    $extractionContext
                );
            }

            return;
        }

        if($target->type == 'generic') {
            $target->type = 'object';
            $reflectionClass = new \ReflectionClass($primitiveType['class']);
            $subContext = $extractionContext->createSubContext();
            $subContext->setParameter('generic-template', $primitiveType['template']);
            $extractionContext->getSwagger()->extract(
                $reflectionClass,
                $target,
                $subContext
            );

            return;
        }

        if ($target->type == "object") {
            $target->type = null;
            $reflectionClass = new \ReflectionClass($primitiveType['class']);
            $rootSchema = $extractionContext->getRootSchema();
            $context = $extractionContext->getParameter('model-context', []);

            $definitionName = $this->getDefinitionName($reflectionClass->name);

            if ($hash = $this->getHash($definitionName, $context)) {
                $definitionName .= '?' . $hash;
            }

            if (!$rootSchema->hasDefinition($definitionName)) {
                $rootSchema->addDefinition($definitionName, $refSchema = new Schema());
                $refSchema->type = "object";
                $extractionContext->getSwagger()->extract(
                    $reflectionClass,
                    $refSchema,
                    $extractionContext
                );
            }

            $target->ref = $rootSchema->getDefinitionReference($definitionName);
            return;
        }

        if (isset($primitiveType['format'])) {
            $target->format = $primitiveType['format'];
        }
    }

    private function getDefinitionName($className)
    {
        foreach ($this->definitionAliases as $class => $alias) {
            if (substr($class, -1) == '\\') {
                if (strpos($className, $class) === 0) {
                    return str_replace($class, $alias, $className);
                }
                continue;
            }

            if ($class == $className) {
                return $alias;
            }
        }

        return $className;
    }

    private function getHash($modelName, array $context = null)
    {
        if (is_null($context)) {
            $context = [];
        }

        $hash = md5(http_build_query($context));

        if (!array_key_exists($modelName, $this->definitionHashes)) {
            $this->definitionHashes[$modelName] = [];
        }

        if (false === ($index = array_search($hash, $this->definitionHashes[$modelName]))) {
            $this->definitionHashes[$modelName][] = $hash;
        }

        return array_search($hash, $this->definitionHashes[$modelName]);
    }

    public static function getPrimitiveType($type)
    {
        if (!is_string($type)) {
            return null;
        }

        if(is_null(self::$typeResolver)) {
            self::$typeResolver = new TypeResolver();
        }

        $result = self::$typeResolver->resolve($type);

        if($result instanceof Collection) {
            return [
                'type' => 'generic',
                'class' => (string)$result->getFqsen(),
                'template' => (string)$result->getValueType()
            ];
        };

        $primitiveType = array();

        $typeOfArray = str_replace('[]', '', $type);
        if ($typeOfArray != $type) {
            if ($typeOfArray !== substr($type, 0, -2)) {
                return null;
            }

            $primitiveType['type'] = 'array';
            $primitiveType['subType'] = $typeOfArray;
            return $primitiveType;
        }

        $types = array(
            'int' => array('type' => 'integer', 'format' => 'int32'),
            'integer' => array('type' => 'integer', 'format' => 'int32'),
            'long' => array('type' => 'integer', 'format' => 'int64'),
            'float' => array('type' => 'number', 'format' => 'float'),
            'double' => array('type' => 'number', 'format' => 'double'),
            'string' => array('type' => 'string'),
            'byte' => array('type' => 'string', 'format' => 'byte'),
            'boolean' => array('type' => 'boolean'),
            'date' => array('type' => 'string', 'format' => 'date'),
            'DateTime' => array('type' => 'string', 'format' => 'date-time'),
            'dateTime' => array('type' => 'string', 'format' => 'date-time'),
            'password' => array('type' => 'string', 'format' => 'password'),
            'array' => array('type' => 'array')
        );

        if (array_key_exists($type, $types)) {
            return $types[$type];
        }

        if (class_exists($type)) {
            return array(
                'type' => 'object',
                'class' => $type
            );
        };

        return null;
    }
}