<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'with-grid' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "with-grid",
 *   label = @Translation("Enable grid"),
 *   settings = {
 *      "weight" = 40,
 *   }
 * )
 */
class WithGrid extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Adds a thin grid around all boxes.'),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 0;
  }

}
