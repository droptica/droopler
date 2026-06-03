<?php

declare(strict_types=1);

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
  #[\Override]
  public function formElement(): array {
    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Stretch this paragraph to 100% browser width.'),
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
