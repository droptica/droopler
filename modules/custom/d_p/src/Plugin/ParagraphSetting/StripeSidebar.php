<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'stripe-sidebar' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "stripe-sidebar",
 *   label = @Translation("Show the price in the sidebar"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 90,
 *   }
 * )
 */
class StripeSidebar extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
    $element = parent::formElement();

    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Works only if "Enable price" is turned on. Enables a black sidebar on the right.'),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 0;
  }

}
