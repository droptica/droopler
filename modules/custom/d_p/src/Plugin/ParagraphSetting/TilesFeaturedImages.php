<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'tiles_featured_images' setting.
 *
 * @ParagraphSetting(
 *   id = "tiles_featured_images",
 *   label = @Translation("Featured images"),
 * )
 */
class TilesFeaturedImages extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#description' => $this->t('Comma separated image numbers. Example: 1,4,7'),
      '#type' => 'textfield',
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return '';
  }

}
