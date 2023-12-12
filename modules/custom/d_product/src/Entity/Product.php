<?php

declare(strict_types = 1);

namespace Drupal\d_product\Entity;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides additional functionality for product gallery.
 */
class Product extends Node {

  /**
   * Prepares 'Ask for product' link.
   *
   * @return \Drupal\Core\Url|void
   *   Url object.
   */
  public function getAskForProductLink() {
    if ($this->hasField('field_d_ask_for_product') && !empty($this->field_d_ask_for_product->value)) {
      return Url::fromUri('internal:/contact#contact-message-feedback-form', [
        'query' => [
          'product' => $this->id(),
        ],
      ]);
    }
  }

}
