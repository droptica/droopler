<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

/**
 * Plugin implementation of the 'column_count_mobile' setting.
 *
 * @ParagraphSetting(
 *   id = "column_count_mobile",
 *   label = @Translation("Column count (mobile)"),
 *   settings = {
 *      "weight" = 92,
 *   }
 * )
 */
class ColumnCountMobile extends ColumnCount {

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 1;
  }

}
