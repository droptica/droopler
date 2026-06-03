<?php

declare(strict_types=1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'with-grid' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "with-grid",
 *   label = @Translation("Enable grid"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 40,
 *   }
 * )
 */
class WithGrid extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function formElement(): array {
    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Adds a thin grid around all boxes.'),
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
