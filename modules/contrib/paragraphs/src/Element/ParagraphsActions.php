<?php

namespace Drupal\paragraphs\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for a paragraphs actions.
 *
 * Paragraphs actions can have two type of actions
 * - actions - this are default actions that are always visible.
 * - dropdown_actions - actions that are in dropdown sub component.
 *
 * Usage example:
 *
 * @code
 * $form['actions'] = [
 *   '#type' => 'paragraphs_actions',
 *   'actions' => $actions,
 *   'dropdown_actions' => $dropdown_actions,
 * ];
 * $dropdown_actions['button'] = array(
 *   '#type' => 'submit',
 * );
 * @endcode
 *
 * @FormElement("paragraphs_actions")
 */
class ParagraphsActions extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#pre_render' => [
        [$class, 'preRenderParagraphsActions'],
      ],
      '#theme' => 'paragraphs_actions',
    ];
  }

  /**
   * Pre render callback for #type 'paragraphs_actions'.
   *
   * @param array $element
   *   Element arrar of a #type 'paragraphs_actions'.
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderParagraphsActions(array $element) {
    $element['#attached']['library'][] = 'paragraphs/drupal.paragraphs.actions';

    if (!empty($element['dropdown_actions'])) {
      foreach (Element::children($element['dropdown_actions']) as $key) {
        $dropdown_action = &$element['dropdown_actions'][$key];
        if (isset($dropdown_action['#ajax'])) {
          $dropdown_action = RenderElement::preRenderAjaxForm($dropdown_action);
        }
        if (empty($dropdown_action['#attributes'])) {
          $dropdown_action['#attributes'] = ['class' => ['paragraphs-dropdown-action']];
        }
        else {
          $dropdown_action['#attributes']['class'][] = 'paragraphs-dropdown-action';
        }
      }
    }

    return $element;
  }

}
