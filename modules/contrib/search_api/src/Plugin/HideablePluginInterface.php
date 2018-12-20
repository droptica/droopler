<?php

namespace Drupal\search_api\Plugin;

/**
 * Defines an interface for plugins that can be hidden.
 */
interface HideablePluginInterface {

  /**
   * Determines whether this plugin should be hidden in the UI.
   *
   * @return bool
   *   TRUE if this processor should be hidden from the user; FALSE otherwise.
   */
  public function isHidden();

}
