<?php

namespace Drupal\search_api_test_no_ui\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a test data type that should be hidden from the UI.
 *
 * @SearchApiDataType(
 *   id = "search_api_test_no_ui",
 *   label = @Translation("No UI data type"),
 *   no_ui = true,
 * )
 */
class NoUi extends DataTypePluginBase {
}
