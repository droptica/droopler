<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for managing title field.
 */
interface TitleInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle(): ?string;

}
