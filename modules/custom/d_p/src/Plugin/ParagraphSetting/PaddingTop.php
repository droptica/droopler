<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'padding-top' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "padding-top",
 *   label = @Translation("Padding Top"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 130,
 *   }
 * )
 */
class PaddingTop extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#description' => $this->t('Choose the size of top padding.'),
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return [
      'padding-top-none' => $this->t('None'),
      'padding-top-small' => $this->t('Small'),
      'padding-top-medium' => $this->t('Medium'),
      'padding-top-large' => $this->t('Large'),
      'padding-top-extra-large' => $this->t('Extra large'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 'padding-top-medium';
  }

}
