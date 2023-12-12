<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'margin-top' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "margin-top",
 *   label = @Translation("Margin Top"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 110,
 *   }
 * )
 */
class MarginTop extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#description' => $this->t('Choose the size of top margin.'),
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return [
      'margin-top-none' => $this->t('None'),
      'margin-top-small' => $this->t('Small'),
      'margin-top-medium' => $this->t('Medium'),
      'margin-top-large' => $this->t('Large'),
      'margin-top-extra-large' => $this->t('Extra large'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 'margin-top-none';
  }

}
