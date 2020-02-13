<?php

namespace Drupal\d_mautic\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'd_mautic_form_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "d_mautic_form_embed",
 *   label = @Translation("Mautic embed"),
 *   field_types = {
 *     "string",
 *   },
 * )
 */
class MauticFormEmbedFormatter extends FormatterBase {

  /**
   * @inheritDoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $value = $item->value;
      if (empty($value)) {
        continue;
      }
      $element[$delta] = [
        '#theme' => 'd_mautic_form_embed',
        '#formUrl' => $value,
      ];
    }

    return $element;
  }

}
