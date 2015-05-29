<?php

namespace Draw\Swagger\Extraction\Extractor\Constraint;

use Draw\Swagger\Extraction\Extractor\ConstraintExtractor;
use Draw\Swagger\Schema\Mixed;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice as SupportedConstraint;

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
     * @param SupportedConstraint $constraint
     * @param ConstraintExtractionContext $context
     */
    public function extractConstraint(Constraint $constraint, ConstraintExtractionContext $context)
    {
        $this->assertSupportConstraint($constraint);


        if ($constraint->callback) {
            if (is_callable(array($className, $constraint->callback))) {
                $choices = call_user_func(array($className, $constraint->callback));
            } elseif (is_callable($constraint->callback)) {
                $choices = call_user_func($constraint->callback);
            } else {
                throw new ConstraintDefinitionException('The Choice constraint expects a valid callback');
            }
        } else {
            $choices = $constraint->choices;
        }

        foreach($choices as $choice) {
            $context->propertySchema->enum[] = new Mixed($choice);
        }
    }
}