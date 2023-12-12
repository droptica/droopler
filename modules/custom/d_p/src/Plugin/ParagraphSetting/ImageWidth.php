<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\Enum\ImageWidthOption;
use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'image_width' setting.
 *
 * @ParagraphSetting(
 *   id = "image_width",
 *   label = @Translation("Image width"),
 * )
 */
class ImageWidth extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

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
    return ImageWidthOption::getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return ImageWidthOption::Normal->value;
  }

}
