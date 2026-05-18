<?php

declare(strict_types=1);

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
  #[\Override]
  public function formElement(): array {
    return [
      '#description' => $this->t('Comma separated image numbers. Example: 1,4,7'),
      '#type' => 'textfield',
    ] + parent::formElement();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getDefaultValue(): mixed {
    return '';
  }

}
