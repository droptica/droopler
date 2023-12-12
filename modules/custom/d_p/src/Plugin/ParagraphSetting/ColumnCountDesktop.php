<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

/**
 * Plugin implementation of the 'column_count_desktop' setting.
 *
 * @ParagraphSetting(
 *   id = "column_count_desktop",
 *   label = @Translation("Column count (desktop)"),
 *   settings = {
 *      "weight" = 90,
 *   }
 * )
 */
class ColumnCountDesktop extends ColumnCount {

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 4;
  }

}
