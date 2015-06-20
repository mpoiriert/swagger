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

        $docBlock = new DocBlock($method->getDocComment());

        foreach ($docBlock->getTagsByName('return') as $returnTag) {
            /* @var $returnTag \phpDocumentor\Reflection\DocBlock\Tag\ReturnTag */
            foreach ($returnTag->getTypes() as $type) {
                $response = new Response();
                $response->schema = $responseSchema = new Schema();
                $operation->responses[200] = $response;

                $subContext = $extractionContext->createSubContext();
                $subContext->setParameter('direction','out');

                $extractionContext->getSwagger()->extract($type, $responseSchema, $subContext);
            }
        }

        foreach ($docBlock->getTagsByName('throws') as $throwTag) {
            /* @var $throwTag \phpDocumentor\Reflection\DocBlock\Tag\ThrowsTag */

            $type = $throwTag->getType();
            $exceptionClass = new \ReflectionClass($type);
            $exception = $exceptionClass->newInstanceWithoutConstructor();
            list($code, $message) = $this->getExceptionInformation($exception);
            $operation->responses[$code] = $exceptionResponse = new Response();

            if ($throwTag->getDescription()) {
                $message = $throwTag->getDescription();
            } else {
                if (!$message) {
                    $exceptionClassDocBlock = new DocBlock($exceptionClass->getDocComment());
                    $message = $exceptionClassDocBlock->getShortDescription();
                }
            }

            $exceptionResponse->description = $message;
        }

        $bodyParameter = null;

        foreach ($operation->parameters as $parameter) {
            if ($parameter instanceof BodyParameter) {
                $bodyParameter = $parameter;
                break;
            }
        }

        foreach ($docBlock->getTagsByName('param') as $paramTag) {
            /* @var $paramTag \phpDocumentor\Reflection\DocBlock\Tag\ParamTag */

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
                    $parameter->description = $paramTag->getDescription();
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
                        $parameter->description = $paramTag->getDescription();
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