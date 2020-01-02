<?php

namespace Drupal\d_p\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * NodePageBottomContent constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch, EntityDisplayRepositoryInterface $entityDisplayRepository, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    /** @var \Drupal\user\Entity\User $currentUser */
    $currentUser = $this->routeMatch->getParameter('user');
    /** @var \Drupal\node\Entity\Node $currentNode */
    $currentNode = $this->routeMatch->getParameter('node');

    if (($targetEntity = $currentUser) || ($targetEntity = $currentNode)) {
      $entityTypeId = $targetEntity->getEntityTypeId();
      $viewBuilder = $this->entityTypeManager->getViewBuilder($entityTypeId);

      /** @var EntityViewDisplay $targetEntityView */
      $targetEntityViewDisplay = $this->entityDisplayRepository->getViewDisplay($entityTypeId, $targetEntity->bundle(), 'page_bottom_content');
      $id = $targetEntityViewDisplay->get('id');
      if ($id !== NULL) {
        $build = $viewBuilder->view($targetEntity, 'page_bottom_content');
      }
    }

    return $build;
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
