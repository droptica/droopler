<?php

declare(strict_types=1);

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
  #[\Override]
  public function formElement(): array {
    return [
      '#description' => $this->t('Choose the size of bottom margin.'),
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
  #[\Override]
  public function getDefaultValue(): mixed {
    return 'margin-bottom-default';
  }

}
