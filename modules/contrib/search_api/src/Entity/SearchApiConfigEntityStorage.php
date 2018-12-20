<?php

namespace Drupal\search_api\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\IndexInterface;

/**
 * Provides a special storage for Search API config entities.
 *
 * This is necessary since post-save hooks would otherwise operate on
 * override-free entities, which is not desirable in our case.
 */
class SearchApiConfigEntityStorage extends ConfigEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    $this->resetCache([$entity->id()]);

    // The entity is no longer new.
    $entity->enforceIsNew(FALSE);

    $overridden_entity = $this->load($entity->id());
    if (isset($entity->original)) {
      $overridden_entity->original = $entity->original;

      // In the case of indexes, we also need to clone the fields to allow the
      // correct detection of renamed field. Conversely, we need to set the new,
      // rename-free fields on the passed index ($entity) so a subsequent save
      // won't false detect field renames.
      if ($entity instanceof IndexInterface) {
        /** @var \Drupal\search_api\IndexInterface $overridden_entity */
        $old_fields = $entity->original->getFields();
        $new_fields = $entity->getFields();
        $saved_fields = $overridden_entity->getFields();
        foreach ($entity->getFieldRenames() as $old_id => $new_id) {
          if (!empty($old_fields[$old_id]) && !empty($saved_fields[$new_id])) {
            $field = clone $new_fields[$new_id];
            $field->setIndex($overridden_entity);
            $saved_fields[$new_id] = $field;
          }
        }
        $overridden_entity->setFields($saved_fields);
      }
    }

    // Allow code to run after saving.
    $overridden_entity->postSave($this, $update);
    $this->invokeHook($update ? 'update' : 'insert', $overridden_entity);

    if ($entity instanceof IndexInterface) {
      // Reset the field instances so saved renames won't be reported anymore.
      $entity->discardFieldChanges();
      $overridden_entity->discardFieldChanges();
    }

    // After saving, this is now the "original entity", and subsequent saves
    // will be updates instead of inserts, and updates must always be able to
    // correctly identify the original entity.
    $entity->setOriginalId($entity->id());
    $overridden_entity->setOriginalId($entity->id());

    unset($entity->original);
    unset($overridden_entity->original);
  }

}
