<?php

declare(strict_types=1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'paragraph-theme' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "paragraph-theme",
 *   label = @Translation("Paragraph Theme"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 100,
 *   }
 * )
 */
class ParagraphTheme extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function formElement(): array {
    return [
      '#description' => $this->t('Choose a color theme for this paragraph.'),
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
      'theme-default' => $this->t('Default'),
      'theme-primary' => $this->t('Primary'),
      'theme-secondary' => $this->t('Secondary'),
      'theme-gray' => $this->t('Gray'),
      'theme-custom' => $this->t('Custom'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getDefaultValue(): mixed {
    return 'theme-default';
  }

}
