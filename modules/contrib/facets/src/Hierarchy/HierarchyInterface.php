<?php

namespace Drupal\facets\Hierarchy;

/**
 * Interface HierarchyInterface.
 */
interface HierarchyInterface {

  /**
   * Retrieve all parent ids for one specific id.
   *
   * @param string $id
   *   An entity id.
   *
   * @return array
   *   An array of all parent ids.
   */
  public function getParentIds($id);

  /**
   * Retrieve all children and nested children for one specific id.
   *
   * @param string $id
   *   An entity id.
   *
   * @return array
   *   An array of all child ids.
   */
  public function getNestedChildIds($id);

  /**
   * Retrieve the direct children for an array of ids.
   *
   * @param array $ids
   *   An array of ids.
   *
   * @return array
   *   Given parent ids as key, value is an array of child ids.
   */
  public function getChildIds(array $ids);

}
