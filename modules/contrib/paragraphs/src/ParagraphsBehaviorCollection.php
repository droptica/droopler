<?php

namespace Drupal\paragraphs;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of paragraphs behavior plugins.
 */
class ParagraphsBehaviorCollection extends DefaultLazyPluginCollection {

  /**
   * All behavior plugin definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\paragraphs\ParagraphsBehaviorInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * Retrieves all enabled behavior plugins.
   */
  public function getEnabled() {
    $this->getAll();
    $enabled = [];
    foreach ($this->getConfiguration() as $key => $value) {
      if (isset($value['enabled']) && $value['enabled'] == TRUE) {
        $enabled[$key] = $this->get($key);
      }
    }
    return $enabled;
  }

  /**
   * Retrieves all behavior plugins definitions and creates an instance for each
   * one.
   */
  public function getAll() {
    // Retrieve all available behavior plugin definitions.
    if (!$this->definitions) {
      $this->definitions = $this->manager->getDefinitions();
    }
    // Ensure that there is an instance of all available behavior plugins.
    // Note that getDefinitions() are keyed by $plugin_id. $instance_id is the
    // $plugin_id for behavior plugins, since a single behavior plugin can only
    // exist once in a paragraphs type.
    foreach ($this->definitions as $plugin_id => $definition) {
      if (!isset($this->pluginInstances[$plugin_id])) {
        $this->initializePlugin($plugin_id);
      }
    }
    return $this->pluginInstances;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $configuration = isset($this->configurations[$instance_id]) ? $this->configurations[$instance_id] : [];
    $this->set($instance_id, $this->manager->createInstance($instance_id, $configuration));
  }

}
