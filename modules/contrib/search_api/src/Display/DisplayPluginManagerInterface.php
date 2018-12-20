<?php

namespace Drupal\search_api\Display;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for the display plugin manager service.
 */
interface DisplayPluginManagerInterface extends PluginManagerInterface {

  /**
   * Returns all known displays.
   *
   * @return \Drupal\search_api\Display\DisplayInterface[]
   *   An array of display plugins, keyed by type identifier.
   */
  public function getInstances();

}
