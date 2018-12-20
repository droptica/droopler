<?php

namespace Drupal\search_api\Plugin\views\argument;

use Drupal\taxonomy\Entity\Term;

/**
 * Defines a contextual filter searching through all indexed taxonomy fields.
 *
 * Note: The plugin annotation below is not misspelled. Due to dependency
 * problems, the plugin is not defined here but in
 * search_api_views_plugins_argument_alter().
 *
 * @ingroup views_argument_handlers
 *
 * ViewsArgument("search_api_term")
 *
 * @see search_api_views_plugins_argument_alter()
 */
class SearchApiTerm extends SearchApiStandard {

  /**
   * {@inheritdoc}
   */
  public function title() {
    if (!empty($this->argument)) {
      $this->fillValue();
      $terms = [];
      foreach ($this->value as $tid) {
        $taxonomy_term = Term::load($tid);
        if ($taxonomy_term) {
          $terms[] = $taxonomy_term->label();
        }
      }

      return $terms ? implode(', ', $terms) : $this->argument;
    }
    else {
      return $this->argument;
    }
  }

}
