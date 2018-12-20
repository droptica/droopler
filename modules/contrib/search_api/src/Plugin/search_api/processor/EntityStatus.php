<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\user\UserInterface;

/**
 * Excludes unpublished nodes from node indexes.
 *
 * @SearchApiProcessor(
 *   id = "entity_status",
 *   label = @Translation("Entity status"),
 *   description = @Translation("Exclude inactive users and unpublished entities (which have a ""Published"" state) from being indexed."),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class EntityStatus extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $interface = EntityPublishedInterface::class;
    foreach ($index->getDatasources() as $datasource) {
      $entity_type_id = $datasource->getEntityTypeId();
      if (!$entity_type_id) {
        continue;
      }
      // We support users and any entities that implement
      // \Drupal\Core\Entity\EntityPublishedInterface.
      if ($entity_type_id === 'user') {
        return TRUE;
      }
      $entity_type = \Drupal::entityTypeManager()
        ->getDefinition($entity_type_id);
      if ($entity_type && $entity_type->entityClassImplements($interface)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      $enabled = TRUE;
      if ($object instanceof EntityPublishedInterface) {
        $enabled = $object->isPublished();
      }
      elseif ($object instanceof UserInterface) {
        $enabled = $object->isActive();
      }
      if (!$enabled) {
        unset($items[$item_id]);
      }
    }
  }

}
