<?php

namespace Drupal\paragraphs\Element;

use Drupal\Core\Render\Element\Operations;
use Drupal\Core\Render\Element\RenderElement;

/**
 * {@inheritdoc}
 *
 * @RenderElement("paragraph_operations")
 */
class ParagraphOperations extends Operations {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return ['#theme' => 'links__dropbutton__operations__paragraphs'] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderDropbutton($element) {
    $element = parent::preRenderDropbutton($element);

    // Attach #ajax events if title is a render array.
    foreach ($element['#links'] as &$link) {
      if (isset($link['title']['#ajax'])) {
        $link['title'] = RenderElement::preRenderAjaxForm($link['title']);
      }
    }

    return $element;
  }

}
