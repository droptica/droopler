<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'tile' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "tile",
 *   label = @Translation("Turn into tile"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 50,
 *   }
 * )
 */
class Tile extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
    $element = parent::formElement();

    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Stretch the background and turn the box into tile.'),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 0;
  }

}
