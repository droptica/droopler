<?php

namespace Drupal\entity_reference_revisions;

/**
 * Allows an entity to define whether it needs to be saved.
 */
interface EntityNeedsSaveInterface {

  /**
   * Checks whether the entity needs to be saved.
   *
   * @return bool
   *   TRUE if the entity needs to be saved.
   */
  public function needsSave();
}
