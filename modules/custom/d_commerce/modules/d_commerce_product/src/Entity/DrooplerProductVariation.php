<?php

namespace Drupal\d_commerce_product\Entity;

use Drupal\commerce_product\Entity\ProductVariation as CommerceProductVariation;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelTrait;

/**
 * Provides an override for commerce_product product variation entity class.
 *
 * @package Drupal\d_commerce_product\Entity
 */
class DrooplerProductVariation extends CommerceProductVariation {

  use LoggerChannelTrait;

  /**
   * {@inheritDoc}
   */
  public function label(): string {
    $variationType = $this->getVariationType();
    if ($variationType && $variationType->shouldGenerateTitle()) {
      $label = parent::label();
    }
    else {
      $label = sprintf("%s %s", $this->getProduct()
        ->getTitle(), parent::label());
    }

    return $label;
  }

  /**
   * Returns variation type of current product variation.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Product variation type.
   */
  protected function getVariationType(): EntityInterface {
    try {
      $variationType = $this->entityTypeManager()
        ->getStorage('commerce_product_variation_type')
        ->load($this->bundle());
    }
    catch (PluginException $e) {
      $this->getLogger('d_commerce_product')->error($e->getMessage());
      $variationType = NULL;
    }

    return $variationType;
  }

}
