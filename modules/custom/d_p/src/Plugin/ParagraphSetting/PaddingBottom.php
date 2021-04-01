<?php

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
  public function formElement(): array {
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
      'padding-bottom-default' => $this->t('Default'),
      'padding-bottom-small' => $this->t('Small'),
      'padding-bottom-big' => $this->t('Big'),
      'padding-bottom-none' => $this->t('None'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 'padding-bottom-default';
  }

}
