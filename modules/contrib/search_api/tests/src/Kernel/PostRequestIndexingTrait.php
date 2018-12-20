<?php

namespace Drupal\Tests\search_api\Kernel;

/**
 * Provides a helper method for triggering post-request indexing.
 */
trait PostRequestIndexingTrait {

  /**
   * Triggers any post-request indexing operations that were registered.
   */
  protected function triggerPostRequestIndexing() {
    \Drupal::getContainer()->get('search_api.post_request_indexing')
      ->destruct();
  }

}
