<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'with-tiles' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "with-tiles",
 *   label = @Translation("Enable tiles"),
 *   settings = {
 *      "weight" = 60,
 *   }
 * )
 */
class WithTiles extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Enables tile view. You have to set all child boxes to tiles by adjusting their settings.'),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 0;
  }

}
