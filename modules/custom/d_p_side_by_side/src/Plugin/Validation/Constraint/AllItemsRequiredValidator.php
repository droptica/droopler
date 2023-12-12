<?php

declare(strict_types = 1);

namespace Drupal\d_p_side_by_side\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Bundle constraint.
 */
class AllItemsRequiredValidator extends ConstraintValidator {

  /**
   * Checks if the passed value is valid.
   *
   * @param mixed $entity
   *   The entity to validate.
   * @param \Drupal\d_p_side_by_side\Plugin\Validation\Constraint\AllItemsRequired $constraint
   *   The constraint to validate.
   */
  public function validate(mixed $entity, Constraint $constraint): void {
    if (!isset($entity)) {
      return;
    }

    /** @var \Drupal\paragraphs\Entity\Paragraph $parentParagraph */
    $parentParagraph = $entity->getParent()->getEntity();
    if (!$parentParagraph->isValidationRequired()) {
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
