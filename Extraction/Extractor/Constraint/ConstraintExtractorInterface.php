<?php

namespace Draw\Swagger\Extraction\Extractor\Constraint;

use Draw\Swagger\Extraction\ExtractorInterface;
use Symfony\Component\Validator\Constraint;

interface ConstraintExtractorInterface extends ExtractorInterface
{
    /**
     * @param Constraint $constraint
     * @return boolean
     */
    public function supportConstraint(Constraint $constraint);

    /**
     * Extract the constraint information
     * @param Constraint $constraint
     * @param ConstraintExtractionContext $context
     * @return void
     */
    public function extractConstraint(Constraint $constraint, ConstraintExtractionContext $context);
}