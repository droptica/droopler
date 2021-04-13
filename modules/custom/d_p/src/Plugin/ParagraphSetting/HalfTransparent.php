<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'half-transparent' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "half-transparent",
 *   label = @Translation("Half transparent"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 10,
 *   }
 * )
 */
class HalfTransparent extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
    $element = parent::formElement();

    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Moves the text to the left and adds a transparent overlay.'),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 0;
  }

}
