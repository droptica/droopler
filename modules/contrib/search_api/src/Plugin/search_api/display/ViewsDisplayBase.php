<?php

namespace Drupal\search_api\Plugin\search_api\display;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\search_api\Display\DisplayPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for Views displays.
 */
abstract class ViewsDisplayBase extends DisplayPluginBase {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|null
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $display */
    $display = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $display->setCurrentRouteMatch($container->get('current_route_match'));

    return $display;
  }

  /**
   * Retrieves the current route match service.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The current route match service.
   */
  public function getCurrentRouteMatch() {
    return $this->currentRouteMatch ?: \Drupal::routeMatch();
  }

  /**
   * Sets the current route match service.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The new current route match service.
   *
   * @return $this
   */
  public function setCurrentRouteMatch(RouteMatchInterface $current_route_match) {
    $this->currentPath = $current_route_match;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    $path = parent::getPath();

    // Recreating a link when a contextual filter is used in the display's path
    // is not possible. So instead we return NULL, which forces most
    // implementations to use the current request's path instead.
    if (strpos($path, '%') !== FALSE) {
      return NULL;
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    $plugin_definition = $this->getPluginDefinition();
    $current_route = $this->getCurrentRouteMatch();
    $view_id = $current_route->getParameter('view_id');
    $display_id = $current_route->getParameter('display_id');
    return $view_id === $plugin_definition['view_id'] &&
        $display_id === $plugin_definition['view_display'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $view = $this->getView();
    $dependencies[$view->getConfigDependencyKey()][] = $view->getConfigDependencyName();

    return $dependencies;
  }

  /**
   * Retrieves the view this search display is based on.
   *
   * @returns \Drupal\views\ViewEntityInterface
   */
  protected function getView() {
    $plugin_definition = $this->getPluginDefinition();
    return $this->getEntityTypeManager()
      ->getStorage('view')
      ->load($plugin_definition['view_id']);
  }

}
