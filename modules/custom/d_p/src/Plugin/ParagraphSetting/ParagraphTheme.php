<?php

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
  public function formElement(): array {
    $element = parent::formElement();

    return [
      '#description' => $this->t('Choose a color theme for this paragraph.'),
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
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
  public function getDefaultValue() {
    return 'theme-default';
  }

}
