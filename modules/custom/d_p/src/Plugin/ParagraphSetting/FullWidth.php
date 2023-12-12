<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'full-width' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "full-width",
 *   label = @Translation("Full width")
 * )
 */
class FullWidth extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
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

}
