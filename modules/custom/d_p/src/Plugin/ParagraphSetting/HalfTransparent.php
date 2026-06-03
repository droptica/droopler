<?php

declare(strict_types=1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'half-transparent' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "half-transparent",
 *   label = @Translation("Half transparent"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 10,
 *   }
 * )
 */
class HalfTransparent extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function formElement(): array {
    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Moves the text to the left and adds a transparent overlay.'),
    ] + parent::formElement();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getDefaultValue(): mixed {
    return 0;
  }

}
