<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Configuration formater' formatter.
 *
 * @FieldFormatter(
 *   id = "field_configuration_formatter",
 *   module = "d_p",
 *   label = @Translation("Settings raw formatter"),
 *   field_types = {
 *     "field_p_configuration_storage"
 *   }
 * )
 */
class ConfigurationFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $item->value,
      ];
    }

    return $elements;
  }

}
