<?php

namespace Drupal\search_api\Entity;

/**
 * Provides a helper method for checking a saved config entity's source.
 */
trait InstallingTrait {

  /**
   * Determines if this config entity is being installed from an extension.
   *
   * @return bool
   *   TRUE if the item is being installed from an extension; FALSE otherwise.
   */
  protected function isInstallingFromExtension() {
    // A configuration item is being installed from an extension if both of the
    // following are true:
    // - It is marked as new.
    // - It has the _core property set.
    // @see \Drupal\core\Config\ConfigInstaller::installConfiguration().
    return ($this->isNew() && !empty($this->_core));
  }

  /**
   * Determines whether the entity is new.
   *
   * Usually an entity is new if no ID exists for it yet. However, entities may
   * be enforced to be new with existing IDs too.
   *
   * @return bool
   *   TRUE if the entity is new, or FALSE if the entity has already been saved.
   *
   * @see \Drupal\Core\Entity\EntityInterface::isNew()
   */
  abstract public function isNew();

}
