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

      // Add compatibility with the Drupal 10.2 Link module changes.
      if (empty($elements[$key]['#options'])) {
        $elements[$key]['#options'] = [
          'type' => $element['#url']->getOption('type'),
          'attributes' => $element['#url']->getOption('attributes'),
        ];
      }
    }

    return $elements;
  }

}
