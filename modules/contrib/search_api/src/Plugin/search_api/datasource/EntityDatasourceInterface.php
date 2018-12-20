<?php

namespace Drupal\search_api\Plugin\search_api\datasource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\search_api\Datasource\DatasourceInterface;

/**
 * Describes an interface for entity datasources.
 */
interface EntityDatasourceInterface extends DatasourceInterface {

  /**
   * Retrieves all indexes that are configured to index the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which to check.
   *
   * @return \Drupal\search_api\IndexInterface[]
   *   All indexes that are configured to index the given entity (using this
   *   datasource class).
   */
  public static function getIndexesForEntity(ContentEntityInterface $entity);

  /**
   * Retrieves all item IDs of entities of the specified bundles.
   *
   * @param int|null $page
   *   The zero-based page of IDs to retrieve, for the paging mechanism
   *   implemented by this datasource; or NULL to retrieve all items at once.
   * @param string[]|null $bundles
   *   (optional) The bundles for which all item IDs should be returned; or NULL
   *   to retrieve IDs from all enabled bundles in this datasource.
   * @param string[]|null $languages
   *   (optional) The languages for which all item IDs should be returned; or
   *   NULL to retrieve IDs from all enabled languages in this datasource.
   *
   * @return string[]|null
   *   An array of all item IDs matching these conditions; or NULL if a page was
   *   specified and there are no more items for that and all following pages.
   *   In case both bundles and languages are specified, they are combined with
   *   OR.
   */
  public function getPartialItemIds($page = NULL, array $bundles = NULL, array $languages = NULL);

}
