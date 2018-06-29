<?php

namespace Draw\Swagger\Extraction\Extractor;

use Doctrine\Common\Annotations\Reader;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\BaseParameter;
use Draw\Swagger\Schema\Operation;

class SwaggerParameterExtractor implements ExtractorInterface
{
    /**
     * @var Reader
     */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function canExtract($source, $target, ExtractionContextInterface $extractionContext)
    {
        if(!$source instanceof \ReflectionMethod) {
            return false;
        }

        if(!$target instanceof Operation) {
            return false;
        }

        return true;
    }

    /**
     * @param \ReflectionMethod $source
     * @param Operation $target
     * @param ExtractionContextInterface $extractionContext
     * @throws ExtractionImpossibleException
     */
    public function extract($source, $target, ExtractionContextInterface $extractionContext)
    {
        if (!$this->canExtract($source, $target, $extractionContext)) {
            throw new ExtractionImpossibleException();
        }

        foreach($this->reader->getMethodAnnotations($source) as $annotation) {
            if($annotation instanceof BaseParameter) {
                $target->parameters[] = $annotation;
            }
        }
    }
}