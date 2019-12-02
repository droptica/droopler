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

  const CSS_CLASS_SETTING_NAME = 'custom_class';
  const HEADING_TYPE_SETTING_NAME = 'heading_type';

  private function getConfigOptions() {
    return [
      self::CSS_CLASS_SETTING_NAME => [
        'title' => $this->t('Additional classes for the paragraph'),
        'type' => 'css',
        'modifiers' => [
          'theme-invert' => [
            'title' => $this->t('Inverted colors'),
            'bundles' => [
              'paragraph' => [
                'd_p_text_paged',
                'd_p_single_text_block',
                'd_p_group_of_text_blocks',
              ]
            ]
          ],
          'full-width' => [
            'title' => $this->t('Full width'),
            'bundles' => [
              'paragraph' => [
                'd_p_group_of_text_blocks'
              ]
            ]
          ]
        ]
      ],
      self::HEADING_TYPE_SETTING_NAME => [
        'title' => $this->t('Heading type'),
        'type' => 'select',
        'options' => [
          'h1' => $this->t('H1'),
          'h2' => $this->t('H2'),
          'h3' => $this->t('H3'),
          'h4' => $this->t('H4'),
          'h5' => $this->t('H5'),
        ],
        'default' => 'h2'
      ],
    ];
  }

  /**
   * Is the modifier available in current bundle?
   *
   * @param array $modifier
   *   The modifier definition.
   *
   * @return bool
   *   Returns TRUE if modifier is in bundle.
   */
  protected function isModifierInBundle($modifier) {
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    if (isset($modifier['bundles'][$entity_type])) {
      return array_search($bundle, $modifier['bundles'][$entity_type]) !== FALSE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $config = !empty($value) ? json_decode($value) : [];

    // Set up the form element for this widget.
    $element += [
      '#type' => 'details',
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    $config_options = $this->getConfigOptions();
    foreach ($config_options as $key => $options) {
      $value = $config->$key ?? '';
      switch ($options['type']) {
        case 'css':
          $classes = preg_split("/[\s,]+/", $value, -1, PREG_SPLIT_NO_EMPTY);
          foreach ($options['modifiers'] as $class => $modifier) {
            if (!$this->isModifierInBundle($modifier)) {
              continue;
            }
            $class_key = array_search($class, $classes);
            if ($class_key === FALSE) {
              $default_value = 0;
            }
            else {
              unset($classes[$class_key]);
              $default_value = 1;
            }
            $element[$class] = [
              '#type' => 'checkbox',
              '#title' => $modifier['title'],
              '#default_value' => $default_value,
              '#attributes' => ['data-modifier' => $class],
            ];
          }

          $element[$key] = [
            '#type' => 'textfield',
            '#title' => $options['title'],
            '#size' => 32,
            '#default_value' => join(' ', $classes),
          ];
          if ($element['#required']) {
            $element[$key]['#required'] = TRUE;
          }
          break;

        case 'select':
          $element[$key] = [
            '#type' => 'select',
            '#title' => $options['title'],
            '#options' => $options['options'],
            '#default_value' => empty($value) ? $options['default'] : $value,
          ];
          if ($element['#required']) {
            $element[$key]['#required'] = TRUE;
          }
          break;

        default:
          $element[$key] = [
            '#type' => 'textfield',
            '#title' => $options['title'],
            '#size' => 32,
            '#default_value' => $value,
          ];
          if ($element['#required']) {
            $element[$key]['#required'] = TRUE;
          }
      }
    }
    return ['value' => $element];
  }

  /**
   * Validate the fields and convert them into a single value as json.
   *
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {
    $values = [];
    $config_options = $this->getConfigOptions();
    foreach ($config_options as $key => $options) {
      if ($options['type'] === 'css') {
        $classes = preg_split("/[\s,]+/", $element[$key]['#value'], -1, PREG_SPLIT_NO_EMPTY);
        foreach ($options['modifiers'] as $class => $modifier) {
          if ($element[$class]['#value'] && $this->isModifierInBundle($modifier)) {
            $classes[] = $class;
          }
        }
        $classes = array_unique($classes);
        $values[$key] = join(' ', $classes);
      }
      else {
        $values[$key] = $element[$key]['#value'];
      }
    }
    $form_state->setValueForElement($element, json_encode($values));
  }

}
