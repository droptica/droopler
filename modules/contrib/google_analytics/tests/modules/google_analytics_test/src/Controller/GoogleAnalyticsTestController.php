<?php

namespace Drupal\google_analytics_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for system_test routes.
 */
class GoogleAnalyticsTestController extends ControllerBase {

  /**
   * Tests setting messages and removing one before it is displayed.
   *
   * @return array
   *   Empty array, we just test the setting of messages.
   */
  public function drupalAddMessageTest() {
    // Set some messages.
    $this->messenger()->addMessage($this->t('Example status message.'), 'status');
    $this->messenger()->addMessage($this->t('Example warning message.'), 'warning');
    $this->messenger()->addMessage($this->t('Example error message.'), 'error');
    $this->messenger()->addMessage($this->t('Example error <em>message</em> with html tags and <a href="http://example.com/">link</a>.'), 'error');

    return [];
  }

}
