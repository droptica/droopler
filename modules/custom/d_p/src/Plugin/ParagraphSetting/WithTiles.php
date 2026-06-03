<?php

declare(strict_types=1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;

/**
 * Plugin implementation of the 'with-tiles' modifier setting.
 *
 * @ParagraphSetting(
 *   id = "with-tiles",
 *   label = @Translation("Enable tiles"),
 *   settings = {
 *      "parent" = "custom_class",
 *      "weight" = 60,
 *   }
 * )
 */
class WithTiles extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function formElement(): array {
    return [
      '#type' => 'checkbox',
      '#description' => $this->t('Enables tile view. You have to set all child boxes to tiles by adjusting their settings.'),
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
