<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;
use Drupal\d_p\Enum\TilesSideOption;

/**
 * Trait for tiles side.
 *
 * @see \Drupal\d_p\TitleInterface.
 */
trait TilesSideTrait {

  use ParagraphSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function getTilesSide(): ?string {
    $settings_field = $this->getParagraphSettingsField();

    return $settings_field?->getSettingValue(ParagraphSettingTypes::TILES_SIDE, TilesSideOption::Left->value);
  }

}
