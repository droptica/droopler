<?php

namespace Drupal\d_commerce_product\Entity;

use Drupal\commerce_product\Entity\ProductVariation as CommerceProductVariation;

/**
 * Provides an override for commerce_product product variation entity class.
 *
 * @package Drupal\d_commerce_product\Entity
 */
class ProductVariation extends CommerceProductVariation {

  /**
   * {@inheritDoc}
   */
  public function label() {
    return sprintf("%s %s", $this->getProduct()->getTitle(), parent::label());
  }

}
