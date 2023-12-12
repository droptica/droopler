<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'custom_class' setting.
 *
 * @ParagraphSetting(
 *   id = "custom_class",
 *   label = @Translation("Additional classes for the paragraph"),
 * )
 */
class CustomClass extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#type' => 'textfield',
      '#subtype' => 'css',
      '#description' => $this->t('Please separate multiple classes by spaces.'),
      '#size' => 32,
      '#weight' => 150,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return '';
  }

}
