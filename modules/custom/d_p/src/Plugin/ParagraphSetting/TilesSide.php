<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\Enum\TilesSideOption;
use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'tiles_side' setting.
 *
 * @ParagraphSetting(
 *   id = "tiles_side",
 *   label = @Translation("Tiles gallery side"),
 * )
 */
class TilesSide extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

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
    return TilesSideOption::getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return TilesSideOption::Left->value;
  }

}
