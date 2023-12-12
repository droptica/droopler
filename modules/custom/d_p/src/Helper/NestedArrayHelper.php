<?php

declare(strict_types = 1);

namespace Drupal\d_p\Helper;

use Drupal\Component\Utility\NestedArray;

/**
 * Provides helpers.
 *
 * Provides helpers to perform operations on nested arrays and array keys of
 * variable depth.
 */
class NestedArrayHelper extends NestedArray {

  /**
   * Unsets the key of an array if the value is equal to privided one.
   *
   * Loops through the prided multidimensional array and searches for provided
   * value. If the value has been found, the array key gets unset.
   *
   * If the target subset of the array is sequential and the unset has been
   * called, the array gets reordered using array_values function to fix the
   * key values after unset.
   *
   * @param array $array
   *   Multidimensional array to perform unset on.
   * @param array $parents
   *   Array with parents of value.
   * @param string $value
   *   Value to search for.
   * @param bool|null $key_existed
   *   (optional) Referenced flag, TRUE if provided key exists and the value is
   *   equal to the provided one, FALSE otherwise.
   */
  public static function unsetValueIfEqualTo(array &$array, array $parents, string $value, &$key_existed = NULL) {
    $unset_key = array_pop($parents);
    $ref = &self::getValue($array, $parents, $key_existed);

    if ($key_existed === FALSE || !is_array($ref) || !array_key_exists($unset_key, $ref)) {
      $key_existed = FALSE;
      return;
    }

    $key_existed = FALSE;
    $is_ref_sequential = [] !== $ref[$unset_key] && array_keys($ref[$unset_key]) === range(0, count($ref[$unset_key]) - 1);
    $key = array_search($value, $ref[$unset_key]);

    if ($key !== FALSE) {
      $key_existed = TRUE;
      unset($ref[$unset_key][$key]);

      if ($is_ref_sequential === TRUE) {
        $ref[$unset_key] = array_values($ref[$unset_key]);
      }
    }
  }

  /**
   * Get ancestors on the given array element based on the parents.
   *
   * @param array $array
   *   Array to be searched.
   * @param array $parents
   *   List of array keys.
   *
   * @return mixed
   *   Array element.
   */
  public static function getParentElement(array $array, array $parents) {
    $element = $array;

    foreach ($parents as $parent) {
      $element = $element[$parent];
    }

    return $element;
  }

}
