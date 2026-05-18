<?php

declare(strict_types=1);

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
  #[\Override]
  public function formElement(): array {
    return [
      '#description' => $this->t('Choose the size of bottom padding.'),
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + parent::formElement();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
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
  #[\Override]
  public function getDefaultValue(): mixed {
    return 'padding-bottom-default';
  }

}
