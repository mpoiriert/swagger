<?php

namespace Draw\Swagger\Extraction\Extractor\Constraint;

use Draw\Swagger\Extraction\Extractor\ConstraintExtractor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length as SupportedConstraint;

class LengthConstraintExtractor extends ConstraintExtractor
{
    /**
     * @param Constraint $constraint
     * @return bool
     */
    public function supportConstraint(Constraint $constraint)
    {
        return $constraint instanceof SupportedConstraint;
    }

    /**
     * @param SupportedConstraint|Constraint $constraint
     * @param ConstraintExtractionContext $context
     */
    public function extractConstraint(Constraint $constraint, ConstraintExtractionContext $context)
    {
        $this->assertSupportConstraint($constraint);
        $context->propertySchema->maxLength = $constraint->max;
        $context->propertySchema->minLength = $constraint->min;
    }
}