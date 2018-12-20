<?php

namespace Drupal\facets_summary\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Defines a confirm form for deleting a facet.
 */
class FacetsSummaryDeleteConfirmForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.facets_facet.collection');
  }

}
