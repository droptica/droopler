<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'full-width' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "full-width",
 *   label = @Translation("Full width"),
 *   settings = {
 *      "parent" = "custom_class",
 *   }
 * )
 */
class FullWidth extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
    $element = parent::formElement();

    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Stretch this paragraph to 100% browser width.'),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedBundles(): array {
    return [
      'd_p_group_of_text_blocks',
      'd_p_carousel',
      'd_p_block',
    ];
  }

}
