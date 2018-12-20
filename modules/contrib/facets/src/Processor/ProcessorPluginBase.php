<?php

namespace Drupal\facets\Processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\facets\FacetInterface;

/**
 * A base class for plugins that implements most of the boilerplate.
 */
class ProcessorPluginBase extends PluginBase implements ProcessorInterface {

  use DependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    // By default, there should be no config form.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    if (!($form_state instanceof SubformStateInterface)) {
      $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
      trigger_error(sprintf('%s::%s() SHOULD receive %s on line %d, but %s was given. More information is available at https://www.drupal.org/node/2774077.', $trace[1]['class'], $trace[1]['function'], SubformStateInterface::class, $trace[1]['line'], get_class($form_state)), E_USER_DEPRECATED);
    }
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function supportsStage($stage_identifier) {
    $plugin_definition = $this->getPluginDefinition();
    return isset($plugin_definition['stages'][$stage_identifier]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultWeight($stage) {
    $plugin_definition = $this->getPluginDefinition();
    return isset($plugin_definition['stages'][$stage]) ? (int) $plugin_definition['stages'][$stage] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return !empty($this->pluginDefinition['locked']);
  }

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    return !empty($this->pluginDefinition['hidden']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $plugin_definition = $this->getPluginDefinition();
    return isset($plugin_definition['description']) ? $plugin_definition['description'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    unset($this->configuration['facet']);
    return $this->configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->addDependency('module', $this->getPluginDefinition()['provider']);
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return NULL;
  }

}
