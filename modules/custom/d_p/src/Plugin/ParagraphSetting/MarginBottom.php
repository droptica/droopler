<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'margin-bottom' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "margin-bottom",
 *   label = @Translation("Margin Bottom"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 120,
 *   }
 * )
 */
class MarginBottom extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
    $element = parent::formElement();

    return [
      '#description' => $this->t('Choose the size of bottom margin.'),
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return [
      'margin-bottom-default' => $this->t('Default'),
      'margin-bottom-small' => $this->t('Small'),
      'margin-bottom-medium' => $this->t('Medium'),
      'margin-bottom-big' => $this->t('Big'),
      'margin-bottom-none' => $this->t('None'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 'margin-bottom-default';
  }

}
