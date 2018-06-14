<?php

/**
 * @file
 * Post update functions for Paragraphs.
 */

use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Set the parent id, type and field name to the already created paragraphs.
 *
 * @param $sandbox
 */
function paragraphs_post_update_set_paragraphs_parent_fields(&$sandbox) {
  // Don't execute the function if paragraphs_update_8003() was already executed
  // which used to do the same.

  $module_schema = drupal_get_installed_schema_version('paragraphs');

  // The state entry 'paragraphs_update_8003_placeholder' is used in order to
  // indicate that the placeholder paragraphs_update_8003() function has been
  // executed, so this function needs to be executed as well. If the non
  // placeholder version of paragraphs_update_8003() got executed already, the
  // state won't be set and we skip this update.
  if ($module_schema >= 8003 && !\Drupal::state()->get('paragraphs_update_8003_placeholder', FALSE)) {
    return;
  }

  if (!isset($sandbox['current_paragraph_field_id'])) {
    $paragraph_field_ids = [];
    // Get all the entity reference revisions fields.
    $map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('entity_reference_revisions');
    foreach ($map as $entity_type_id => $info) {
      foreach ($info as $name => $data) {
        if (FieldStorageConfig::loadByName($entity_type_id, $name)->getSetting('target_type') == 'paragraph') {
          $paragraph_field_ids[] = "$entity_type_id.$name";
        }
      }
    }

    if (!$paragraph_field_ids) {
      // There are no paragraph fields. Return before initializing the sandbox.
      return;
    }

    // Initialize the sandbox.
    $sandbox['current_paragraph_field_id'] = 0;
    $sandbox['paragraph_field_ids'] = $paragraph_field_ids;
    $sandbox['max'] = count($paragraph_field_ids);
    $sandbox['progress'] = 0;
  }

  /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
  $field_storage = FieldStorageConfig::load($sandbox['paragraph_field_ids'][$sandbox['current_paragraph_field_id']]);
  // For revisionable entity types, we load and update all revisions.
  $target_entity_type = \Drupal::entityTypeManager()->getDefinition($field_storage->getTargetEntityTypeId());
  if ($target_entity_type->isRevisionable()) {
    $revision_id = $target_entity_type->getKey('revision');
    $entity_ids = \Drupal::entityQuery($field_storage->getTargetEntityTypeId())
      ->condition($field_storage->getName(), NULL, 'IS NOT NULL')
      ->range($sandbox['progress'], Settings::get('paragraph_limit', 50))
      ->allRevisions()
      ->sort($revision_id, 'ASC')
      ->accessCheck(FALSE)
      ->execute();
  }
  else {
    $id = $target_entity_type->getKey('id');
    $entity_ids = \Drupal::entityQuery($field_storage->getTargetEntityTypeId())
      ->condition($field_storage->getName(), NULL, 'IS NOT NULL')
      ->range($sandbox['progress'], Settings::get('paragraph_limit', 50))
      ->sort($id, 'ASC')
      ->accessCheck(FALSE)
      ->execute();
  }
  foreach ($entity_ids as $revision_id => $entity_id) {
    // For revisionable entity types, we load a specific revision otherwise load
    // the entity.
    if ($target_entity_type->isRevisionable()) {
      $host_entity = \Drupal::entityTypeManager()
        ->getStorage($field_storage->getTargetEntityTypeId())
        ->loadRevision($revision_id);
    }
    else {
      $host_entity = \Drupal::entityTypeManager()
        ->getStorage($field_storage->getTargetEntityTypeId())
        ->load($entity_id);
    }
    foreach ($host_entity->get($field_storage->getName()) as $field_item) {
      // Skip broken and already updated references (e.g. Nested paragraphs).
      if ($field_item->entity && empty($field_item->entity->parent_type->value)) {
        // Set the parent fields and save, ensure that no new revision is
        // created.
        $field_item->entity->parent_type = $field_storage->getTargetEntityTypeId();
        $field_item->entity->parent_id = $host_entity->id();
        $field_item->entity->parent_field_name = $field_storage->getName();
        $field_item->entity->setNewRevision(FALSE);
        $field_item->entity->save();
      }
    }
  }
  // Continue with the next paragraph_field_id when the loaded entities are less
  // than paragraph_limit.
  if (count($entity_ids) < Settings::get('paragraph_limit', 50)) {
    $sandbox['current_paragraph_field_id']++;
    $sandbox['progress'] = 0;
  }
  else {
    $sandbox['progress'] += Settings::get('paragraph_limit', 50);
  }
  // Update #finished, 1 if the the whole update has finished.
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current_paragraph_field_id'] / $sandbox['max']);
}
