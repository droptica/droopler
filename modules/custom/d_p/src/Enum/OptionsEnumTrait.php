<?php

declare(strict_types = 1);

namespace Drupal\d_p\Enum;

/**
 * Trait for getting drupal form element options array.
 *
 * @see \Drupal\d_p\Enum\OptionsEnumInterface
 */
trait OptionsEnumTrait {

  /**
   * {@inheritdoc}
   */
  public static function getOptions(): array {
    $cases = self::cases();
    $options = [];

    foreach ($cases as $case) {
      $options[$case->value] = $case->getLabel();
    }

    return $options;
  }

}
