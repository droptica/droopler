<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\Enum\TextAlignOptions;
use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'text_align' setting.
 *
 * @ParagraphSetting(
 *   id = "text_align",
 *   label = @Translation("Text Align"),
 *   settings = {
 *      "weight" = 10,
 *   }
 * )
 */
class TextAlign extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return [
      TextAlignOptions::Start->value => $this->t('Left'),
      TextAlignOptions::End->value => $this->t('Right'),
      TextAlignOptions::Center->value => $this->t('Center'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return TextAlignOptions::Center->value;
  }

}
