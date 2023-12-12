<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface;

/**
 * Trait for manage paragraph settings field.
 */
trait ParagraphSettingTrait {

  /**
   * Get paragraph settings field.
   *
   * @return \Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface|\Drupal\Core\Field\FieldItemListInterface|null
   *   Paragraph settings field, or NULL if not found.
   */
  protected function getParagraphSettingsField(): ConfigurationStorageFieldItemListInterface|FieldItemListInterface|null {
    if ($this->hasField('field_d_settings')) {
      return $this->get('field_d_settings');
    }

    return NULL;
  }

}
