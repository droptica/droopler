<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for managing full width paragraph setting.
 *
 * @see \Drupal\d_p\FullWidthInterface.
 */
trait FullWidthTrait {

  use BooleanSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function isFullWidth(): bool {
    return $this->getBooleanSettingValue(ParagraphSettingTypes::FULL_WIDTH);
  }

}
