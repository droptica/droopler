<?php

namespace Drupal\modules_weight\Utility;

/**
 * Provides Form Element helper methods.
 *
 * @ingroup utility
 */
class FormElement {

  /**
   * Prepares the delta for the weight field.
   *
   * If a module has a weight higher then 100 (or lower than 100), it will use
   * that value as delta and the '#weight' field will turn into a textfield most
   * likely.
   *
   * @param int $weight
   *   The weight.
   *
   * @return int
   *   The weight.
   */
  public static function getMaxDelta($weight) {
    $delta = 100;

    // Typecasting to int.
    $weight = (int) $weight;

    if ($weight > $delta) {
      return $weight;
    }

    if ($weight < -100) {
      return $weight * -1;
    }

    return $delta;
  }

}
