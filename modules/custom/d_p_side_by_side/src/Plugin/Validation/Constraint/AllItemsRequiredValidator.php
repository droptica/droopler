<?php

namespace Drupal\d_p_side_by_side\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Bundle constraint.
 */
class AllItemsRequiredValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    if ($constraint->number > 0 && $entity->count() != $constraint->number) {
      $this->context->addViolation($constraint->message, [
        '%number' => $constraint->number,
        '%name' => $constraint->name,
      ]);
    }
  }

}
