<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for form layout setting.
 */
trait FormLayoutTrait {

  use ParagraphSettingTrait;

  /**
   * Returns form layout setting.
   */
  public function getFormLayout(): string {
    $settings_field = $this->getParagraphSettingsField();

    return $settings_field?->getSettingValue(ParagraphSettingTypes::FORM_LAYOUT);
  }

}
