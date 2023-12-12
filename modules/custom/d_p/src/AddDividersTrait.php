<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for managing add dividers paragraph setting.
 */
trait AddDividersTrait {

  use BooleanSettingTrait;

  /**
   * Check if items have dividers between them.
   *
   * @return bool
   *   TRUE if have dividers, FALSE otherwise.
   */
  public function hasDividers(): bool {
    return $this->getBooleanSettingValue(ParagraphSettingTypes::ADD_DIVIDERS);
  }

}
