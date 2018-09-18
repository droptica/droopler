<?php

namespace Drupal\modules_weight\Utility;

/**
 * Provides generic array sorting helper methods.
 *
 * @ingroup utility
 */
class SortArray {

  /**
   * Sorts a structured array by the 'weight' and 'name element.
   *
   * Note that the sorting is by the 'weight' array element, not by the render
   * element property '#weight'.
   *
   * Callback for uasort().
   *
   * @param array $a
   *   First item for comparison. The compared items should be associative
   *   arrays that optionally include a 'weight' element. For items without a
   *   'weight' element, a default value of 0 will be used.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public static function sortByWeightAndName(array $a, array $b) {
    $a_weight = is_array($a) && isset($a['weight']) ? $a['weight'] : 0;
    $b_weight = is_array($b) && isset($b['weight']) ? $b['weight'] : 0;

    if ($a_weight == $b_weight) {
      return ($a['name'] < $b['name'] ? -1 : 1);
    }

    return $a_weight < $b_weight ? -1 : 1;
  }

}
