<?php

declare(strict_types = 1);

namespace Drupal\d_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'Search page link' Block.
 *
 * @Block(
 *   id = "search_page_link",
 *   admin_label = @Translation("Search page link"),
 *   category = @Translation("Search"),
 * )
 */
class SearchPageLink extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      [
        '#type' => 'link',
        '#title' => 'Search',
        '#attributes' => [
          'class' => ['search-page-link'],
          'target' => '_self',
        ],
        '#url' => Url::fromRoute('search.view'),
      ],
    ];
  }

}
