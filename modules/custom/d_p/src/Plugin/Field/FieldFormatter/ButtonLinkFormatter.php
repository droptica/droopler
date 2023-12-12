<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'Button link formater' formatter.
 *
 * @FieldFormatter(
 *   id = "button_link_formatter",
 *   module = "d_p",
 *   label = @Translation("Button link formatter"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class ButtonLinkFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = parent::viewElements($items, $langcode);

    foreach ($elements as $key => $element) {
      unset($elements[$key]['#type']);
      $elements[$key]['#theme'] = 'd_button_link';
    }

    return $elements;
  }

}
