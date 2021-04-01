<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'side_tiles_layout' setting.
 *
 * @ParagraphSetting(
 *   id = "side_tiles_layout",
 *   label = @Translation("Tiles gallery side"),
 * )
 */
class SideTilesLayout extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
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
      'left' => $this->t('Left'),
      'right' => $this->t('Right'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 'left';
  }

}
