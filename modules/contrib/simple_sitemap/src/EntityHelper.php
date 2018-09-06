<?php

namespace Drupal\simple_sitemap;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;

/**
 * Class EntityHelper
 * @package Drupal\simple_sitemap
 */
class EntityHelper {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * EntityHelper constructor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $database) {
    $this->entityTypeManager = $entityTypeManager;
    $this->db = $database;
  }

  /**
   * Gets an entity's bundle name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return string
   */
  public function getEntityInstanceBundleName(EntityInterface $entity) {
    return $entity->getEntityTypeId() === 'menu_link_content'
      // Menu fix.
      ? $entity->getMenuName() : $entity->bundle();
  }

  /**
   * Gets the entity type id for a bundle.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return null|string
   */
  public function getBundleEntityTypeId(EntityInterface $entity) {
    return $entity->getEntityTypeId() === 'menu'
      // Menu fix.
      ? 'menu_link_content' : $entity->getEntityType()->getBundleOf();
  }

  /**
   * Returns objects of entity types that can be indexed.
   *
   * @return array
   *   Objects of entity types that can be indexed by the sitemap.
   */
  public function getSupportedEntityTypes() {
    $entity_types = $this->entityTypeManager->getDefinitions();

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface
        || !method_exists($entity_type, 'getBundleEntityType')
        || !$entity_type->hasLinkTemplate('canonical')) {
        unset($entity_types[$entity_type_id]);
      }
    }
    return $entity_types;
  }

  /**
   * Checks whether an entity type does not provide bundles.
   *
   * @param string $entity_type_id
   * @return bool
   */
  public function entityTypeIsAtomic($entity_type_id) {

    // Menu fix.
    if ($entity_type_id === 'menu_link_content') {
      return FALSE;
    }

    $entity_types = $this->entityTypeManager->getDefinitions();

    if (!isset($entity_types[$entity_type_id])) {
      // todo: Throw exception.
    }

    return empty($entity_types[$entity_type_id]->getBundleEntityType()) ? TRUE : FALSE;
  }

  /**
   * @param $url_object
   * @return object|null
   */
  public function getEntityFromUrlObject(Url $url_object) {
    return $url_object->isRouted()
    && !empty($route_parameters = $url_object->getRouteParameters())
    && $this->entityTypeManager->getDefinition($entity_type_id = key($route_parameters), FALSE)
      ? $this->entityTypeManager->getStorage($entity_type_id)
        ->load($route_parameters[$entity_type_id])
      : NULL;
  }

  /**
   * @param $entity_type_name
   * @param $entity_id
   * @return array
   */
  public function getEntityImageUrls($entity_type_name, $entity_id) {
    $query = $this->db->select('file_managed', 'fm');
    $query->fields('fm', ['uri']);
    $query->join('file_usage', 'fu', 'fu.fid = fm.fid');
    $query->condition('fm.filemime', 'image/%', 'LIKE');
    $query->condition('fu.type', $entity_type_name);
    $query->condition('fu.id', $entity_id);

    foreach ($query->execute() as $row) {
      $imageUris[] = file_create_url($row->uri);
    }

    return !empty($imageUris) ? $imageUris : [];
  }

}
