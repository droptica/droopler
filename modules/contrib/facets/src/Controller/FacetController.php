<?php

namespace Drupal\facets\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\facets\FacetInterface;

/**
 * Provides route responses for facets.
 */
class FacetController extends ControllerBase {

  /**
   * Returns a form to edit a facet on a Search API index.
   *
   * @param \Drupal\facets\FacetInterface $facets_facet
   *   Facet currently being edited.
   *
   * @return array
   *   The facet edit form.
   */
  public function editForm(FacetInterface $facets_facet) {
    $facet = $this->entityTypeManager()
      ->getStorage('facets_facet')
      ->load($facets_facet->id());
    return $this->entityFormBuilder()->getForm($facet, 'default');
  }

  /**
   * Returns the page title for an facets's "View" tab.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet that is displayed.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(FacetInterface $facet) {
    return new FormattableMarkup('@title', ['@title' => $facet->label()]);
  }

}
