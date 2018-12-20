<?php

namespace Drupal\search_api_test;

use Drupal\search_api\Backend\BackendInterface;
use Drupal\search_api\IndexInterface;

/**
 * Holds method overrides for test plugins.
 */
class MethodOverrides {

  /**
   * Provides a generic method override for the test backend.
   *
   * @param \Drupal\search_api\Backend\BackendInterface $backend
   *   The backend plugin on which the method was called.
   *
   * @return true
   *   Always returns TRUE, to cater to those methods that expect a return
   *   value.
   */
  public static function overrideTestBackendMethod(BackendInterface $backend) {
    if ($backend->getConfiguration() !== ['test' => 'foobar']) {
      trigger_error('Critical server method called with incorrect backend configuration.', E_USER_ERROR);
    }
    return TRUE;
  }

  /**
   * Provides an override for the test backend's indexItems() method.
   *
   * @param \Drupal\search_api\Backend\BackendInterface $backend
   *   The backend plugin on which the method was called.
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index for which items should be indexed.
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   An array of items to be indexed, keyed by their item IDs.
   *
   * @return string[]
   *   The array keys of $items.
   */
  public static function overrideTestBackendIndexItems(BackendInterface $backend, IndexInterface $index, array $items) {
    if ($backend->getConfiguration() !== ['test' => 'foobar']) {
      trigger_error('Server method indexItems() called with incorrect backend configuration.', E_USER_ERROR);
    }
    return array_keys($items);
  }

}
