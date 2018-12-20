<?php

namespace Drupal\search_api_test_no_ui\Plugin\search_api\tracker;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\search_api\tracker\Basic;

/**
 * Provides a test tracker that should be hidden from the UI.
 *
 *  @SearchApiTracker(
 *   id = "search_api_test_no_ui",
 *   label = @Translation("No UI tracker"),
 *   no_ui = true,
 * )
 */
class NoUi extends Basic implements PluginFormInterface {
}
