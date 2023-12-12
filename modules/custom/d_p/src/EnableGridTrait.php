<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for 'Enable grid' option.
 *
 * @see \Drupal\d_p\EnableGridInterface.
 */
trait EnableGridTrait {

  use ParagraphSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function hasEnabledGrid(): bool {
    $settings_field = $this->getParagraphSettingsField();

    return (bool) $settings_field?->getSettingValue(ParagraphSettingTypes::WITH_GRID, FALSE);
  }

}
