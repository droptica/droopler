<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\Enum\ImageSideOption;
use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'image_side' setting.
 *
 * @ParagraphSetting(
 *   id = "image_side",
 *   label = @Translation("Image side"),
 * )
 */
class ImageSide extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return ImageSideOption::getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return ImageSideOption::Left->value;
  }

}
