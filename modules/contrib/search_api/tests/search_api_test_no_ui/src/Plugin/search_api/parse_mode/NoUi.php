<?php

namespace Drupal\search_api_test_no_ui\Plugin\search_api\parse_mode;

use Drupal\search_api\ParseMode\ParseModePluginBase;

/**
 * Provides a parse mode that should be hidden from the UI.
 *
 * @SearchApiParseMode(
 *   id = "search_api_test_no_ui",
 *   label = @Translation("No UI parse mode"),
 *   no_ui = true,
 * )
 */
class NoUi extends ParseModePluginBase {

  /**
   * {@inheritdoc}
   */
  public function parseInput($keys) {
    return $keys;
  }

}
