<?php

namespace Drupal\d_color\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the color_widget default input widget.
 *
 * @FieldWidget(
 *   id = "d_color_field_widget_default",
 *   label = @Translation("Color field default"),
 *   field_types = {
 *     "d_color_field_type"
 *   }
 * )
 */
class ColorFieldWidgetDefault extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['color'] = $element + [
      '#type' => 'd_color',
      '#maxlength' => 7,
      '#size' => 7,
      '#required' => $element['#required'],
      '#default_value' => $items[$delta]->color ?? '',
      '#placeholder' => $this->getSetting('placeholder_color'),
    ];
    $element['#type'] = 'container';

    return $element;
  }

}
