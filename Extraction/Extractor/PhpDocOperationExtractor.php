<?php

namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\BodyParameter;
use Draw\Swagger\Schema\Operation;
use Draw\Swagger\Schema\Response;
use Draw\Swagger\Schema\Schema;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Compound;
use ReflectionMethod;

class PhpDocOperationExtractor implements ExtractorInterface
{
    private $exceptionResponseCodes = array();

    /**
     * Return if the extractor can extract the requested data or not.
     *
     * @param $source
     * @param $type
     * @param ExtractionContextInterface $extractionContext
     * @return boolean
     */
    public function canExtract($source, $type, ExtractionContextInterface $extractionContext)
    {
        if (!$source instanceof ReflectionMethod) {
            return false;
        }

        if (!$type instanceof Operation) {
            return false;
        }

        return true;
    }

    private function createDocBlock($comment)
    {
        return DocBlockFactory::createInstance()->create($comment);
    }

    /**
     * Extract the requested data.
     *
     * The system is a incrementing extraction system. A extractor can be call before you and you must complete the
     * extraction.
     *
     * @param ReflectionMethod $method
     * @param Operation $operation
     * @param ExtractionContextInterface $extractionContext
     */
    public function extract($method, $operation, ExtractionContextInterface $extractionContext)
    {
        if (!$this->canExtract($method, $operation, $extractionContext)) {
            throw new ExtractionImpossibleException();
        }

        $docBlock = $this->createDocBlock($method->getDocComment());

        if(!$operation->summary) {
            $operation->summary = $docBlock->getSummary() ?: null;
        }

        if(!$operation->description) {
            $operation->description = (string)$docBlock->getDescription() ?: null;
        }

        foreach ($docBlock->getTagsByName('return') as $returnTag) {
            /* @var $returnTag DocBlock\Tags\Return_ */

            $type = $returnTag->getType();
            // If multiple return types are specified in return tag, separate them
            if ($type instanceof Compound) {
                $types = explode('|', (string)$type);
            } else {
                $types = [(string)$type];
            }

            foreach ($types as $type) {
                $response = new Response();
                $response->schema = $responseSchema = new Schema();
                $response->description = (string)$returnTag->getDescription() ?: null;
                $operation->responses[200] = $response;

                $subContext = $extractionContext->createSubContext();
                $subContext->setParameter('direction','out');

                $extractionContext->getSwagger()->extract($type, $responseSchema, $subContext);
            }
        }

        if($docBlock->getTagsByName('deprecated')) {
           $operation->deprecated = true;
        }

        foreach ($docBlock->getTagsByName('throws') as $throwTag) {
            /* @var $throwTag DocBlock\Tags\Throws */

            $type = $throwTag->getType();
            $exceptionClass = new \ReflectionClass((string)$type);
            $exception = $exceptionClass->newInstanceWithoutConstructor();
            list($code, $message) = $this->getExceptionInformation($exception);
            $operation->responses[$code] = $exceptionResponse = new Response();

            if ((string)$throwTag->getDescription()) {
                $message = (string)$throwTag->getDescription();
            } else {
                if (!$message) {
                    $exceptionClassDocBlock = $this->createDocBlock($exceptionClass->getDocComment());
                    $message = $exceptionClassDocBlock->getSummary();
                }
            }

            $exceptionResponse->description = (string)$message ?: null;
        }

        $bodyParameter = null;

        foreach ($operation->parameters as $parameter) {
            if ($parameter instanceof BodyParameter) {
                $bodyParameter = $parameter;
                break;
            }
        }

        foreach ($docBlock->getTagsByName('param') as $paramTag) {
            /* @var $paramTag DocBlock\Tags\Param */

            $parameterName = trim($paramTag->getVariableName(), '$');

            $parameter = null;
            foreach ($operation->parameters as $existingParameter) {
                if ($existingParameter->name == $parameterName) {
                    $parameter = $existingParameter;
                    break;
                }
            }

            if (!is_null($parameter)) {
                if (!$parameter->description) {
                    $parameter->description = (string)$paramTag->getDescription() ?: null;
                }

                if (!$parameter->type) {
                    $parameter->type = $paramTag->getType();
                }
                continue;
            }

            if (!is_null($bodyParameter)) {
                /* @var BodyParameter $bodyParameter */
                if (isset($bodyParameter->schema->properties[$parameterName])) {
                    $parameter = $bodyParameter->schema->properties[$parameterName];

                    if (!$parameter->description) {
                        $parameter->description = (string)$paramTag->getDescription() ?: null;
                    }

                    if (!$parameter->type) {
                        $subContext = $extractionContext->createSubContext();
                        $subContext->setParameter('direction','in');
                        $extractionContext->getSwagger()->extract($paramTag->getType(), $parameter, $subContext);
                    }

                    continue;
                }
            }
        }
    }

    private function getExceptionInformation(\Exception $exception)
    {
        foreach ($this->exceptionResponseCodes as $class => $information) {
            if ($exception instanceof $class) {
                return $information;
            }
        }

        return array(500, null);
    }

    public function registerExceptionResponseCodes($exceptionClass, $code = 500, $message = null)
    {
        $this->exceptionResponseCodes[$exceptionClass] = array($code, $message);
    }
}