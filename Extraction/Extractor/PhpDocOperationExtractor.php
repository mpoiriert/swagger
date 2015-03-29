<?php

namespace Draw\Swagger\Extraction\Extractor;

use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\ExtractorInterface;
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
     * @param Operation $type
     * @param ExtractionContextInterface $extractionContext
     */
    public function extract($method, $operation, ExtractionContextInterface $extractionContext)
    {
        if (!$this->canExtract($method, $operation, $extractionContext)) {
            throw new ExtractionImpossibleException();
        }

        $docBlock = new DocBlock($method->getDocComment());
        $swagger = $extractionContext->getRootSchema();

        foreach ($docBlock->getTagsByName('return') as $returnTag) {
            /* @var $returnTag \phpDocumentor\Reflection\DocBlock\Tag\ReturnTag */
            foreach ($returnTag->getTypes() as $type) {
                if(!$swagger->hasDefinition($type)) {
                    $modelSchema = new Schema();
                    $extractionContext->getSwagger()->extract($type, $modelSchema, $extractionContext);
                    $swagger->addDefinition($type, $modelSchema);
                }

                $response = new Response();
                $response->schema = $responseSchema = new Schema();
                $operation->responses[200] = $response;
                $responseSchema->ref = $swagger->getDefinitionReference($type);
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