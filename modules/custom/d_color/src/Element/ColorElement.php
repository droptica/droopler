<?php

declare(strict_types = 1);

namespace Drupal\d_color\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Color;

/**
 * Provides a form element for choosing a color with reset button.
 *
 * Properties:
 * - #default_value: Default value, in a format like #ffffff.
 *
 * Example usage:
 * @code
 * $form['d_color'] = [
 *   '#type' => 'd_color',
 *   '#title' => $this->t('Color'),
 *   '#default_value' => '#ffffff',
 * ]
 * @endcode
 *
 * @FormElement("d_color")
 */
class ColorElement extends Color {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateColor'],
      ],
      '#pre_render' => [
        [$class, 'preRenderColor'],
      ],
      '#theme' => 'input__color',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Prepares a #type 'd_color' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderColor($element) { // phpcs:ignore
    $element['#attributes']['type'] = 'color';
    Element::setAttributes($element, [
      'id',
      'name',
      'value',
    ]);
    static::setAttributes($element, ['form-d-color']);

    $element['#attached'] = [
      'library' => [
        'd_color/color',
      ],
    ];

    $element['#children'] = [
      'reset' => [
        '#type' => 'button',
        '#value' => t('Reset color'),
        '#attributes' => [
          'id' => [
            'reset-button',
          ],
          'class' => [
            'button',
            'js-form-submit',
            'form-submit',
            'm-1',
          ],
        ],
      ],
    ];

    return $element;
  }

}
