<?php

namespace Drupal\search_api\Plugin\views\cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\cache\Tag;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a tag-based cache plugin for use with Search API views.
 *
 * This cache plugin basically sets an unlimited cache life time for the view,
 * but the view will be refreshed when any of its cache tags are invalidated.
 *
 * Use this for search results views that are fully controlled by a single
 * Drupal instance. A common use case is a website that uses the default
 * database search backend and does not index any external data sources.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "search_api_tag",
 *   title = @Translation("Search API (tag-based)"),
 *   help = @Translation("Cache results until the associated cache tags are invalidated. Useful for small sites that use the database search backend. <strong>Caution:</strong> Can lead to stale results and might harm performance for complex search pages.")
 * )
 */
class SearchApiTagCache extends Tag {

  use SearchApiCachePluginTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $cache */
    $cache = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $cache->setEntityTypeManager($container->get('entity_type.manager'));

    return $cache;
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
  public function getCacheTags() {
    $tags = $this->view->storage->getCacheTags();
    $tag = 'search_api_list:' . $this->getQuery()->getIndex()->id();
    $tags = Cache::mergeTags([$tag], $tags);
    return $tags;
  }

}
