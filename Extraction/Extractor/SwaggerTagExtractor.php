<?php

namespace Draw\Swagger\Extraction\Extractor;


use Doctrine\Common\Annotations\Reader;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\Operation as SupportedTarget;
use Draw\Swagger\Schema\Tag;
use ReflectionMethod as SupportedSource;

class SwaggerTagExtractor implements ExtractorInterface
{
    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(Reader $reader)
    {
        $this->annotationReader = $reader;
    }

    /**
     * Return if the extractor can extract the requested data or not.
     *
     * @param SupportedSource $source
     * @param SupportedTarget $target
     * @param ExtractionContextInterface $extractionContext
     * @return boolean
     */
    public function canExtract($source, $target, ExtractionContextInterface $extractionContext)
    {
        if (!$source instanceof SupportedSource) {
            return false;
        }

        if (!$target instanceof SupportedTarget) {
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
     * @param SupportedSource $source
     * @param SupportedTarget $target
     * @param ExtractionContextInterface $extractionContext
     */
    public function extract($source, $target, ExtractionContextInterface $extractionContext)
    {
        foreach($this->annotationReader->getMethodAnnotations($source) as $annotation) {
            if($annotation instanceof Tag) {
                $target->tags[] = $annotation->name;
            }
        }
    }
}