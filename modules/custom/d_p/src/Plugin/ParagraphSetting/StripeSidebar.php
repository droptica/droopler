<?php

declare(strict_types=1);

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
  #[\Override]
  public function formElement(): array {
    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Works only if "Enable price" is turned on. Enables a black sidebar on the right.'),
    ] + parent::formElement();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getDefaultValue(): mixed {
    return 0;
  }

}
