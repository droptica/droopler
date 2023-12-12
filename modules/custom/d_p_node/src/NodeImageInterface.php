<?php

declare(strict_types = 1);

namespace Drupal\d_p_node;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Interface for managing image field.
 */
interface NodeImageInterface {

  /**
   * Get image.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   Image.
   */
  public function getImage(): ?FieldItemListInterface;

  /**
   * Gets image field name.
   *
   * This is needed for ::getImage() method.
   *
   * @return string
   *   Image field name.
   */
  public function getImageFieldName(): string;

}
