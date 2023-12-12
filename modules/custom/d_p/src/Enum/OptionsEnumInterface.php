<?php

declare(strict_types = 1);

namespace Drupal\d_p\Enum;

/**
 * Interface for labeled enum.
 */
interface OptionsEnumInterface {

  /**
   * Gets a drupal form element options array.
   *
   * @return array
   *   An array of options with key of option value and value of option label.
   */
  public static function getOptions(): array;

}
