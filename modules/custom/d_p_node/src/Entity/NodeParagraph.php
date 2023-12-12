<?php

declare(strict_types = 1);

namespace Drupal\d_p_node\Entity;

use Drupal\d_p\ColumnCountInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for d_p_node paragraphs.
 */
class NodeParagraph extends Paragraph {

  /**
   * Gets parent entity columns settings.
   *
   * @return array
   *   Array of column settings.
   */
  public function getParentColumnSettings(): array {
    $parent = $this->getParentEntity();
    if ($parent instanceof ColumnCountInterface) {
      return $parent->getColumnSettings();
    }

    return [];
  }

}
