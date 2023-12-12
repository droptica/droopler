<?php

declare(strict_types = 1);

namespace Drupal\d_p\Enum;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for labeled enum.
 */
interface LabeledEnumInterface {

  /**
   * Returns translatable label for enum value.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Label for enum value.
   */
  public function getLabel(): TranslatableMarkup;

}
