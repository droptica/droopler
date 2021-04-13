<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'with-divider' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "with-divider",
 *   label = @Translation("Add dividers"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 20,
 *   }
 * )
 */
class WithDivider extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
    $element = parent::formElement();

    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Add vertical lines between all elements.'),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 0;
  }

}
