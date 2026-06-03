<?php

declare(strict_types=1);

namespace Drupal\d_update\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for the Update entity.
 */
interface UpdateInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Whether the update hook ran successfully.
   */
  public function wasSuccessfulByHook(): bool;

  /**
   * Set the `successful_by_hook` flag.
   *
   * @return $this
   */
  public function setSuccessfulByHook(bool $success): self;

}
