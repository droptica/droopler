<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

/**
 * Plugin implementation of the 'column_count_mobile' setting.
 *
 * @ParagraphSetting(
 *   id = "column_count_mobile",
 *   label = @Translation("Column count (mobile)"),
 *   settings = {
 *      "weight" = 2,
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
