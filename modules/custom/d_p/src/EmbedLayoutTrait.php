<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for embed layout setting.
 */
trait EmbedLayoutTrait {

  use ParagraphSettingTrait;

  /**
   * Gets embed side option.
   *
   * @return string
   *   Embed side option value.
   */
  public function getEmbedSide(): string {
    $settings_field = $this->getParagraphSettingsField();

    return $settings_field?->getSettingValue(ParagraphSettingTypes::EMBED_LAYOUT);
  }

}
