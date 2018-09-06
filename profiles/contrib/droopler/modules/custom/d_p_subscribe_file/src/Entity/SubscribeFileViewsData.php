<?php

namespace Drupal\d_p_subscribe_file\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ktype entities.
 */
class SubscribeFileViewsData extends EntityViewsData {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
