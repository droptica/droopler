<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'left_side_content' modifier.
 *
 * @ParagraphSetting(
 *   id = "left_side_content",
 *   label = @Translation("Left side content"),
 *   settings = {
 *      "weight" = 10,
 *   }
 * )
 */
class LeftSideContent extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Moves the text to the left and adds a transparent overlay.'),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 0;
  }

}
