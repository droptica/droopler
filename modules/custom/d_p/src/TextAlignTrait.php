<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;
use Drupal\d_p\Enum\TextAlignOptions;

/**
 * Trait for text alignment.
 */
trait TextAlignTrait {

  use ParagraphSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function getTextAlign(): string {
    $settings = $this->getParagraphSettingsField();

    return $settings?->getSettingValue(ParagraphSettingTypes::TEXT_ALIGN, TextAlignOptions::Center->value);
  }

}
