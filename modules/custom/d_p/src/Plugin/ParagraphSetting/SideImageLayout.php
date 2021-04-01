<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'side_image_layout' setting.
 *
 * @ParagraphSetting(
 *   id = "side_image_layout",
 *   label = @Translation("Image side"),
 * )
 */
class SideImageLayout extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

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
      'left-wide' => $this->t('Left (wide)'),
      'right-wide' => $this->t('Right (wide)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 'left';
  }

}
