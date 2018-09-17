<?php

namespace Drupal\d_product\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'MobileFiltersSubmit' Block.
 *
 * @Block(
 *   id = "mobile_filters_submit",
 *   admin_label = @Translation("Mobile filters submit"),
 *   category = @Translation("Facets"),
 * )
 */
class MobileFiltersSubmit extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'inside' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('Close Filters'),
        '#attributes' => [
          'class' => ['mobile-filter-close'],
          'type' => ['button'],
//          'data-toggle' => ['collapse'],
          'data-target' => ['.region-sidebar-left'],
          'aria-expanded' => ['false'],
          'aria-controls' => ['.region-sidebar-left'],
        ],
      ],
      'closeme' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('x'),
        '#attributes' => [
          'class' => ['mobile-filter-close-top', 'd-none'],
          'type' => ['button'],
//          'data-toggle' => ['collapse'],
          'data-target' => ['.region-sidebar-left'],
          'aria-expanded' => ['false'],
          'aria-controls' => ['.region-sidebar-left'],
        ],
      ],
    ];
  }
}
