<?php

namespace Draw\Swagger\Extraction\Extractor\Constraint;

use Draw\Swagger\Extraction\Extractor\ConstraintExtractor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice as SupportedConstraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class ChoiceConstraintExtractor extends ConstraintExtractor
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

        if ($constraint->callback) {
            if (!is_callable($constraint->callback)) {
                throw new ConstraintDefinitionException('The Choice constraint expects a valid callback');
            }
            $choices = call_user_func($constraint->callback);
        } else {
            $choices = $constraint->choices;
        }

        foreach ($choices as $choice) {
            $context->propertySchema->enum[] = $choice;
        }
    }
}