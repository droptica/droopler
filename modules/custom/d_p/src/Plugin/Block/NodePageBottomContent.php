<?php

namespace Drupal\d_p\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Node Page Bottom Content' Block.
 *
 * @Block(
 *   id = "node_page_bottom_content",
 *   admin_label = @Translation("Node Page Bottom Content"),
 *   category = @Translation("Droopler"),
 * )
 */
class NodePageBottomContent extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * NodePageBottomContent constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('current_route_match'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    /** @var \Drupal\user\Entity\User $currentUser */
    $currentUser = $this->routeMatch->getParameter('user');
    /** @var \Drupal\node\Entity\Node $currentNode */
    $currentNode = $this->routeMatch->getParameter('node');

    if ( ($targetEntity = $currentUser) || ($targetEntity = $currentNode) ) {
      $entityTypeId = $targetEntity->getEntityTypeId();
      $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($entityTypeId);

      /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository */
      $entityDisplayRepository = \Drupal::service('entity_display.repository');

      /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $targetEntityView */
      $targetEntityViewDisplay = $entityDisplayRepository->getViewDisplay($entityTypeId, $targetEntity->bundle(), 'page_bottom_content');
      $id = $targetEntityViewDisplay->get('id');
      if ($id !== NULL ) {
        return $viewBuilder->view($targetEntity, 'page_bottom_content');

      }
    }

    return [
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.path';
    $contexts[] = 'url.query_args';
    return $contexts;
  }

}
