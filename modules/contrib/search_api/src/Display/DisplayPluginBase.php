<?php

namespace Drupal\search_api\Display;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Url;
use Drupal\search_api\Plugin\HideablePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class from which other display classes may extend.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. The definition includes the following keys:
 * - id: The unique, system-wide identifier of the display class.
 * - label: Human-readable name of the display class, translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiDisplay(
 *   id = "my_display",
 *   label = @Translation("My display"),
 *   description = @Translation("A few words about this search display"),
 *   index = "search_index",
 *   path = "/my/custom/search",
 * )
 * @endcode
 *
 * @see \Drupal\search_api\Annotation\SearchApiDisplay
 * @see \Drupal\search_api\Display\DisplayPluginManager
 * @see \Drupal\search_api\Display\DisplayInterface
 * @see plugin_api
 */
abstract class DisplayPluginBase extends HideablePluginBase implements DisplayInterface {

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack|null
   */
  protected $currentPath;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $display = new static($configuration, $plugin_id, $plugin_definition);

    $display->setCurrentPath($container->get('path.current'));
    $display->setEntityTypeManager($container->get('entity_type.manager'));

    return $display;
  }

  /**
   * Retrieves the current path service.
   *
   * @return \Drupal\Core\Path\CurrentPathStack
   *   The current path service.
   */
  public function getCurrentPath() {
    return $this->currentPath ?: \Drupal::service('path.current');
  }

  /**
   * Sets the current path service.
   *
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The new current path service.
   *
   * @return $this
   */
  public function setCurrentPath(CurrentPathStack $current_path) {
    $this->currentPath = $current_path;
    return $this;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::service('entity_type.manager');
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The new entity type manager.
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
  public function label() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    $plugin_definition = $this->getPluginDefinition();
    return $this->getEntityTypeManager()
      ->getStorage('search_api_index')
      ->load($plugin_definition['index']);
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    if ($path = $this->getPath()) {
      return Url::fromUserInput($path);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    $plugin_definition = $this->getPluginDefinition();
    if (!empty($plugin_definition['path'])) {
      return $plugin_definition['path'];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    if ($path = $this->getPath()) {
      $current_path = $this->getCurrentPath()->getPath();
      return $current_path == $path;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];

    // By default, add dependencies to the module providing this display and to
    // the index it is based on.
    $definition = $this->getPluginDefinition();
    $dependencies['module'][] = $definition['provider'];

    $index = $this->getIndex();
    $dependencies[$index->getConfigDependencyKey()][] = $index->getConfigDependencyName();

    return $dependencies;
  }

}
