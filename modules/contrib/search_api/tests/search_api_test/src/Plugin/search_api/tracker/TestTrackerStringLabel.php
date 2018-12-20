<?php

namespace Drupal\search_api_test\Plugin\search_api\tracker;

/**
 * Provides a tracker implementation which uses a FIFO-like processing order.
 *
 * @SearchApiTracker(
 *   id = "search_api_test_string_label",
 *   label = "&quot;String label&quot; test tracker",
 *   description = "This is the <em>test tracker with string label</em> plugin description.",
 * )
 */
class TestTrackerStringLabel extends TestTracker {
}
