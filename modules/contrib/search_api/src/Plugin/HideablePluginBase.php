<?php

namespace Drupal\search_api\Plugin;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base class for plugins that can be hidden.
 */
class HideablePluginBase extends PluginBase implements HideablePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    return !empty($this->pluginDefinition['no_ui']);
  }

}
