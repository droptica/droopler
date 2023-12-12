<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'stripe-sidebar' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "with-price-in-sidebar",
 *   label = @Translation("Show the price in the sidebar"),
 *   settings = {
 *      "weight" = 81,
 *   }
 * )
 */
class WithPriceInSidebar extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
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
