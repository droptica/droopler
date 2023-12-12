<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ImageSideOption;
use Drupal\d_p\Enum\ImageWidthOption;
use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for managing image settings.
 *
 * @see \Drupal\d_p\ImageWidthInterface
 * @see \Drupal\d_p\ImageSideInterface
 */
trait ImageSettingsTrait {

  use ParagraphSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function getImageSide(): ?string {
    $settings_field = $this->getParagraphSettingsField();

    return $settings_field?->getSettingValue(ParagraphSettingTypes::IMAGE_SIDE, ImageSideOption::Left->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getImageWidth(): ?string {
    $settings_field = $this->getParagraphSettingsField();

    return $settings_field?->getSettingValue(ParagraphSettingTypes::IMAGE_WIDTH, ImageWidthOption::Normal->value);
  }

}
