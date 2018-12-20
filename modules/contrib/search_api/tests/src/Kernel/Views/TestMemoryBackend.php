<?php

namespace Drupal\Tests\search_api\Kernel\Views;

use Drupal\Core\Cache\MemoryBackend;

/**
 * A variant of the memory cache backend that allows to change the request time.
 */
class TestMemoryBackend extends MemoryBackend {

  /**
   * The simulated request time.
   *
   * @var int|null
   */
  protected $requestTime;

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return $this->requestTime ?: parent::getRequestTime();
  }

  /**
   * Sets the request time.
   *
   * @param int $time
   *   The request time to set.
   */
  public function setRequestTime($time) {
    $this->requestTime = $time;
  }

}
