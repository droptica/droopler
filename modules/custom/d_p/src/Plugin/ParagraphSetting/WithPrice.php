<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'with-price' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "with-price",
 *   label = @Translation("Enable price"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 80,
 *   }
 * )
 */
class WithPrice extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
    $element = parent::formElement();

    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Show a dynamic price on the right, it requires a JS script to connect to a data source.'),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 0;
  }

}
