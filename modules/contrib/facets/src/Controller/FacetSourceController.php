<?php

namespace Drupal\facets\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\facets\Form\FacetSourceEditForm;

/**
 * Provides route responses for facet source configuration.
 */
class FacetSourceController extends ControllerBase {

  /**
   * Configuration for the facet source.
   *
   * @param string $facets_facet_source
   *   The plugin id.
   *
   * @return array
   *   A renderable array containing the form.
   */
  public function facetSourceConfigForm($facets_facet_source) {
    // Returns the render array of the FacetSourceConfigForm.
    return $this->formBuilder()->getForm(FacetSourceEditForm::class);
  }

}
