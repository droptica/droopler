<?php

namespace Drupal\paragraphs_library\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Contains routes for library item functionality.
 */
class LibraryItemRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = parent::getRoutes($entity_type);
    // Display library items using default theme.
    if ($canonical_route = $route_collection->get("entity.{$entity_type->id()}.canonical")) {
      $canonical_route->setOption('_admin_route', FALSE);
    }
    return $route_collection;
  }

}
