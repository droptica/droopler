<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'heading_type' setting.
 *
 * @ParagraphSetting(
 *   id = "heading_type",
 *   label = @Translation("Heading type"),
 *   settings = {
 *      "weight" = -10
 *   }
 * )
 */
class HeadingType extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#description' => $this->t('Select the type of heading to use with this paragraph.'),
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return [
      'h1' => $this->t('H1'),
      'h2' => $this->t('H2'),
      'h3' => $this->t('H3'),
      'h4' => $this->t('H4'),
      'h5' => $this->t('H5'),
      'div' => $this->t('Normal text'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 'h2';
  }

}
