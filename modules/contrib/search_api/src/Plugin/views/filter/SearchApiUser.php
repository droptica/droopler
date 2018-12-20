<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\user\Plugin\views\filter\Name;

/**
 * Defines a filter for filtering on user references.
 *
 * Based on \Drupal\user\Plugin\views\filter\Name.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_user")
 */
class SearchApiUser extends Name {

  use SearchApiFilterTrait;

  /**
   * {@inheritdoc}
   */
  public function operators() {
    return [
      'or' => [
        'title' => $this->t('Is one of'),
        'short' => $this->t('or'),
        'short_single' => $this->t('='),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ],
      'and' => [
        'title' => $this->t('Is all of'),
        'short' => $this->t('and'),
        'short_single' => $this->t('='),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ],
      'not' => [
        'title' => $this->t('Is none of'),
        'short' => $this->t('not'),
        'short_single' => $this->t('<>'),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ],
      'empty' => [
        'title' => $this->t('Is empty (NULL)'),
        'method' => 'opEmpty',
        'short' => $this->t('empty'),
        'values' => 0,
      ],
      'not empty' => [
        'title' => $this->t('Is not empty (NOT NULL)'),
        'method' => 'opEmpty',
        'short' => $this->t('not empty'),
        'values' => 0,
      ],
    ];
  }

}
