<?php

declare(strict_types=1);

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
  #[\Override]
  public function formElement(): array {
    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Stretch the background and turn the box into tile.'),
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
