<?php

namespace Drupal\d_product\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "reset_button",
 *   admin_label = @Translation("Reset Filters"),
 *   category = @Translation("Facets"),
 * )
 */
class ResetButton extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#markup' => $this->t('Reset '),
    );
  }

}