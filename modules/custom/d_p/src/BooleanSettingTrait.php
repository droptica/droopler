<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingInterface;

/**
 * Trait for managing boolean-type settings.
 *
 * @see \Drupal\d_p\BooleanSettingInterface.
 */
trait BooleanSettingTrait {

  use ParagraphSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function getBooleanSettingValue(ParagraphSettingInterface $field_setting): bool {
    $settings_field = $this->getParagraphSettingsField();

    return $settings_field->hasClass($field_setting->value) ?: (bool) $settings_field?->getSettingValue($field_setting, FALSE);
  }

}
