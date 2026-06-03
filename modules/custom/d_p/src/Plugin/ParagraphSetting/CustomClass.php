<?php

declare(strict_types=1);

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
  #[\Override]
  public function formElement(): array {
    return [
      '#type' => 'textfield',
      '#subtype' => 'css',
      '#description' => $this->t('Please separate multiple classes by spaces.'),
      '#size' => 32,
      '#weight' => 150,
    ] + parent::formElement();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getDefaultValue(): mixed {
    return '';
  }

}
