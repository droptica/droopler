<?php

namespace Drupal\d_p\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Settings widget' widget.
 *
 * @FieldWidget(
 *   id = "field_d_p_set_settings",
 *   module = "d_p",
 *   label = @Translation("Settings"),
 *   field_types = {
 *     "field_p_configuration_storage"
 *   }
 * )
 */
class SettingsWidget extends WidgetBase {

  private function getConfigOptions() {
    return [
      'custom_class' => [
        'title' => $this->t('Custom Class'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    if (!empty($value)) {
      $config = json_decode($value);
    }
    else {
      $config = [];
    }

    // Set up the form element for this widget.
    $element += [
      '#type' => 'details',
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    $configOptions = $this->getConfigOptions();
    foreach ($configOptions as $key => $options) {
      $element[$key] = [
        '#type' => 'textfield',
        '#title' => $options['title'],
        '#size' => 32,
        '#default_value' => isset($config->$key) ? $config->$key : '',
      ];
      if ($element['#required']) {
        $element[$key]['#required'] = TRUE;
      }
    }
    return ['value' => $element];
  }

  /**
   * Validate the fields and convert them into a single value as json.
   */
  public function validate($element, FormStateInterface $form_state) {
    $values = [];
    $configOptions = $this->getConfigOptions();
    foreach ($configOptions as $key => $option) {
      $values[$key] = $element[$key]['#value'];
    }
    $form_state->setValueForElement($element, json_encode($values));
  }

}
