<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for left side content.
 *
 * @see \Drupal\d_p\LeftSideContentInterface.
 */
trait LeftSideContentTrait {

  use ParagraphSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function hasLeftSideContent(): bool {
    $settings_field = $this->getParagraphSettingsField();

    return (bool) $settings_field?->getSettingValue(ParagraphSettingTypes::LEFT_SIDE_CONTENT, FALSE);
  }

}
