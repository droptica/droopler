<?php

declare(strict_types=1);

namespace Drupal\d_p\Helper;

use Drupal\Component\Utility\NestedArray;

/**
 * Provides helpers for manipulating nested arrays.
 *
 * Operations on nested arrays and array keys of variable depth that aren't
 * covered by Drupal's core NestedArray helper.
 */
class NestedArrayHelper extends NestedArray {

  /**
   * Unset the key of a nested array if its value equals the provided one.
   *
   * Walks `$parents` to a leaf, then removes the entry whose value equals
   * `$value`. If the leaf is a sequential list the keys are re-indexed via
   * `array_values()` to avoid gaps after the unset.
   *
   * @param array $array
   *   Multidimensional array to perform the unset on.
   * @param array $parents
   *   Parents path to the value.
   * @param string $value
   *   Value to search for.
   * @param bool|null $key_existed
   *   Out-flag: TRUE if the value was found and removed, FALSE otherwise.
   */
  public static function unsetValueIfEqualTo(array &$array, array $parents, string $value, ?bool &$key_existed = NULL): void {
    $unset_key = array_pop($parents);
    $ref = &self::getValue($array, $parents, $key_existed);

    if ($key_existed === FALSE || !is_array($ref) || !array_key_exists($unset_key, $ref)) {
      $key_existed = FALSE;
      return;
    }

    $key_existed = FALSE;
    $is_ref_sequential = $ref[$unset_key] !== []
      && array_keys($ref[$unset_key]) === range(0, count($ref[$unset_key]) - 1);
    $key = array_search($value, $ref[$unset_key], FALSE);

    if ($key === FALSE) {
      return;
    }

    $key_existed = TRUE;
    unset($ref[$unset_key][$key]);

    if ($is_ref_sequential) {
      $ref[$unset_key] = array_values($ref[$unset_key]);
    }
  }

  /**
   * Get an ancestor element of `$array` reached by walking `$parents`.
   *
   * @param array $array
   *   Array to be searched.
   * @param array $parents
   *   List of array keys.
   *
   * @return mixed
   *   The reached array element.
   */
  public static function getParentElement(array $array, array $parents): mixed {
    $element = $array;
    foreach ($parents as $parent) {
      $element = $element[$parent];
    }
    return $element;
  }

}
