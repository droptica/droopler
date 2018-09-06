<?php

namespace Drupal\tvi\Service;

use Drupal\taxonomy\TermInterface;

/**
 * Define an interface for returning a view assigned to a taxonomy term or
 * vocabulary.
 *
 *
 */
interface TaxonomyViewsIntegratorManagerInterface {

  /**
   * Return the taxonomy term View per taxonomy view integrator settings.
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   * @return array
   */
  public function getTaxonomyTermView(TermInterface $taxonomy_term);

}