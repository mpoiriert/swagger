<?php

namespace Draw\Swagger\Extraction;

interface ExtractorInterface
{
    /**
     * Return if the extractor can extract the requested data or not.
     *
     * @param mixed $source
     * @param mixed $target
     * @param ExtractionContextInterface $extractionContext
     * @return boolean
     */
    public function canExtract($source, $target, ExtractionContextInterface $extractionContext);

    /**
     * Extract the requested data.
     *
     * The system is a incrementing extraction system. A extractor can be call before you and you must complete the
     * extraction.
     *
     * @param mixed $source
     * @param mixed $target
     * @param ExtractionContextInterface $extractionContext
     */
    public function extract($source, $target, ExtractionContextInterface $extractionContext);
}