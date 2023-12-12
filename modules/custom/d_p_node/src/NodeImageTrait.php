<?php

declare(strict_types = 1);

namespace Drupal\d_p_node;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Trait for nodes with image field.
 *
 * @see \Drupal\d_p_node\NodeImageInterface
 */
trait NodeImageTrait {

  /**
   * {@inheritdoc}
   */
  public function getImage(): ?FieldItemListInterface {
    $field_name = $this->getImageFieldName();

    if ($this->hasField($field_name)) {
      return $this->get($field_name);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getImageFieldName(): string;

}
