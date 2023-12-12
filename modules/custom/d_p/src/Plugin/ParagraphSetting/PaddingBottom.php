<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'padding-bottom' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "padding-bottom",
 *   label = @Translation("Padding Bottom"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 140,
 *   }
 * )
 */
class PaddingBottom extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#description' => $this->t('Choose the size of bottom padding.'),
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return [
      'padding-bottom-none' => $this->t('None'),
      'padding-bottom-small' => $this->t('Small'),
      'padding-bottom-medium' => $this->t('Medium'),
      'padding-bottom-large' => $this->t('Large'),
      'padding-bottom-extra-large' => $this->t('Extra large'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 'padding-bottom-medium';
  }

}
