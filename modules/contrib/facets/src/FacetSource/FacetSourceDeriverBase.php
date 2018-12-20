<?php

namespace Drupal\facets\FacetSource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Display\DisplayPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for facet source derivers.
 */
abstract class FacetSourceDeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The search api display plugin manager.
   *
   * @var \Drupal\search_api\Display\DisplayPluginManager
   */
  protected $searchApiDisplayPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $deriver = new static();

    $module_list = $container->get('module_handler')->getModuleList();
    if (!in_array('search_api', array_keys($module_list))) {
      return;
    }

    $entity_type_manager = $container->get('entity_type.manager');
    $deriver->setEntityTypeManager($entity_type_manager);

    $translation = $container->get('string_translation');
    $deriver->setStringTranslation($translation);

    $search_api_display_plugin_manager = $container->get('plugin.manager.search_api.display');
    $deriver->setSearchApiDisplayPluginManager($search_api_display_plugin_manager);

    return $deriver;
  }

  /**
   * Retrieves the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::service('entity_type.manager');
  }

  /**
   * Sets the entity manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);
    return isset($derivatives[$derivative_id]) ? $derivatives[$derivative_id] : NULL;
  }

  /**
   * Sets search api's display plugin manager.
   *
   * @param \Drupal\search_api\Display\DisplayPluginManager $search_api_display_plugin_manager
   *   The plugin manager.
   */
  public function setSearchApiDisplayPluginManager(DisplayPluginManager $search_api_display_plugin_manager) {
    $this->searchApiDisplayPluginManager = $search_api_display_plugin_manager;
  }

  /**
   * Returns the display plugin manager.
   *
   * @return \Drupal\search_api\Display\DisplayPluginManager
   *   The plugin manager.
   */
  public function getSearchApiDisplayPluginManager() {
    return $this->searchApiDisplayPluginManager;
  }

  /**
   * Compares two plugin definitions according to their labels.
   *
   * @param array $a
   *   A plugin definition, with at least a "label" key.
   * @param array $b
   *   Another plugin definition.
   *
   * @return int
   *   An integer less than, equal to, or greater than zero if the first
   *   argument is considered to be respectively less than, equal to, or greater
   *   than the second.
   */
  public function compareDerivatives(array $a, array $b) {
    return strnatcasecmp($a['label'], $b['label']);
  }

}
