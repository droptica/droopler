<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

/**
 * Plugin implementation of the 'column_count_tablet' setting.
 *
 * @ParagraphSetting(
 *   id = "column_count_tablet",
 *   label = @Translation("Column count (tablet)"),
 *   settings = {
 *      "weight" = 91,
 *   }
 * )
 */
class ColumnCountTablet extends ColumnCount {

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 3;
  }

}
