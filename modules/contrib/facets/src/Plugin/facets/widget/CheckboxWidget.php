<?php

namespace Drupal\facets\Plugin\facets\widget;

use Drupal\facets\FacetInterface;

/**
 * The checkbox / radios widget.
 *
 * @FacetsWidget(
 *   id = "checkbox",
 *   label = @Translation("List of checkboxes"),
 *   description = @Translation("A configurable widget that shows a list of checkboxes"),
 * )
 */
class CheckboxWidget extends LinksWidget {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $build = parent::build($facet);
    $build['#attributes']['class'][] = 'js-facets-checkbox-links';
    $build['#attached']['library'][] = 'facets/drupal.facets.checkbox-widget';
    return $build;
  }

}
