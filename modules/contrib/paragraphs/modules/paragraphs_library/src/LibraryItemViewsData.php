<?php

namespace Drupal\paragraphs_library;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the paragraphs library item entity type.
 */
class LibraryItemViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['paragraphs_library_item_field_data']['paragraphs__target_id']['relationship'] = [
      'title' => $this->t('Paragraph relationship'),
      'help' => $this->t('Allows users to add paragraphs entity fields.'),
      'id' => 'standard',
      'base' => 'paragraphs_item_field_data',
      'base field' => 'id',
      'label' => $this->t('Paragraphs'),
    ];
    return $data;
  }

}
