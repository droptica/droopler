<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'featured_images' setting.
 *
 * @ParagraphSetting(
 *   id = "featured_images",
 *   label = @Translation("Featured images"),
 * )
 */
class FeaturedImages extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
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
