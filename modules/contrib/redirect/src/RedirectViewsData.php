<?php

namespace Drupal\redirect;

use Drupal\views\EntityViewsData;

/**
 * Provides views integration for Redirect entities.
 */
class RedirectViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Views defaults to the 'redirect_source' field that is configured as
    // the redirect label. Since this is a composed field, change the default
    // field to its 'path' value.
    $data['redirect']['table']['base']['defaults']['field'] = 'redirect_source__path';
    return $data;
  }

}
