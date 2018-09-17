<?php

namespace Drupal\d_product\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'MobileFilters' Block.
 *
 * @Block(
 *   id = "mobile_filters",
 *   admin_label = @Translation("Mobile filters"),
 *   category = @Translation("Facets"),
 * )
 */
class MobileFilters extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'inside' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('Filters'),
        '#attributes' => [
          'class' => ['mobile-filter', 'collapsed'],
          'type' => ['button'],
//          'data-toggle' => ['collapse'],
          'data-target' => ['.region-sidebar-left'],
          'aria-expanded' => ['false'],
          'aria-controls' => ['.region-sidebar-left'],
          'data-closed' => [t('Filters')],
          'data-open' => [t('Close Filters')],
        ],
      ],
    ];
  }
}
