<?php

declare(strict_types=1);

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
  #[\Override]
  public function formElement(): array {
    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Add vertical lines between all elements.'),
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
