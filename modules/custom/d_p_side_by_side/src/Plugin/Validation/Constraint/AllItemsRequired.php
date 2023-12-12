<?php

declare(strict_types = 1);

namespace Drupal\d_p_side_by_side\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Make field require adding all items up to the limit if set.
 *
 * @Constraint(
 *   id = "AllItemsRequired",
 *   label = @Translation("Make field require adding all items up to the limit", context="Validation"),
 * )
 */
class AllItemsRequired extends Constraint {

  /**
   * Message shown when validation fails.
   *
   * @var string
   */
  public string $message = '%name field requires exactly %number items.';

  /**
   * The number option.
   *
   * @var int
   */
  public int $number;

  /**
   * The name option.
   *
   * @var string
   */
  public string $name;

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return ['number', 'name'];
  }

}
