<?php

namespace Drupal\Tests\paragraphs\Traits;

/**
 * Test trait providing helpers to query latest entities created.
 */
trait ParagraphsLastEntityQueryTrait {

  /**
   * Gets the latest entity created of a given type.
   *
   * Will fail the test if there is no entity of that type.
   *
   * @param string $entity_type_id
   *   The storage name of the entity.
   * @param bool $load
   *   (optional) Whether or not the return thould be the loaded entity.
   *   Defaults to FALSE.
   *
   * @return mixed
   *   The ID of the latest created entity of that type. If $load is TRUE, will
   *   use ::loadUnchanged() to get a fresh version of the entity object and
   *   return it.
   */
  protected function getLastEntityOfType($entity_type_id, $load = FALSE) {
    $query_result = \Drupal::entityQuery($entity_type_id)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();
    $entity_id = reset($query_result);
    if (empty($entity_id)) {
      $this->fail('Could not find latest entity of type: ' . $entity_type_id);
    }
    if ($load) {
      return \Drupal::entityTypeManager()->getStorage($entity_type_id)
        ->loadUnchanged($entity_id);
    }
    else {
      return $entity_id;
    }
  }

}
