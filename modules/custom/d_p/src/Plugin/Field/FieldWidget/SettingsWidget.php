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

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const CSS_CLASS_SETTING_NAME = 'custom_class';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const HEADING_TYPE_SETTING_NAME = 'heading_type';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const COLUMN_COUNT_SETTING_NAME = 'column_count';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const COLUMN_COUNT_MOBILE_SETTING_NAME = 'column_count_mobile';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const COLUMN_COUNT_TABLET_SETTING_NAME = 'column_count_tablet';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const PARAGRAPH_FEATURED_IMAGES = 'featured_images';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const PARAGRAPH_SETTING_FORM_LAYOUT = 'form_layout';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const PARAGRAPH_SETTING_EMBED_LAYOUT = 'embed_layout';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const PARAGRAPH_SETTING_SIDE_IMAGE_LAYOUT = 'side_image_layout';

  /**
   * @deprecated Use \Drupal\d_p\ParagraphSettingTypesInterface instead.
   */
  const PARAGRAPH_SETTING_SIDE_TILES_LAYOUT = 'side_tiles_layout';

  /**
   * Get configuration options for fields in paragraph settings.
   */
  private static function getConfigOptions() {
    /** @var \Drupal\d_p\Service\ParagraphSettingsConfigurationManager $settingsManager */
    $settingsManager = \Drupal::service('d_p.paragraph_settings_configuration.manager');

    $form = $settingsManager->loadMultiple();

    \Drupal::moduleHandler()->alter('d_settings', $form);
    return $form;
  }

  /**
   * Is the element available in the bundle of widget's target?
   *
   * @param array $list
   *   The form element bundle list, keyed by entity type and bundle, like $list['paragraph']['sample_paragraph'].
   *   There is a magic "all" item, that means "available for all bundles of this entity type".
   *
   * @return bool
   */
  protected function inBundle(array $list) {
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    if (isset($list[$entity_type])) {
      if (array_search('all', $list[$entity_type]) !== FALSE) {
        // Introduce "all" keyword to avoid listing all bundles.
        return TRUE;
      }
      else {
        return array_search($bundle, $list[$entity_type]) !== FALSE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $field_name = $items->getFieldDefinition()->getName();

    // Set up the form element for this widget.
    $element += [
      '#type' => 'fieldset',
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    $config_options = self::getConfigOptions();
    foreach ($config_options as $key => $options) {
      // If the widget is not available in the current bundle, just skip it.
      $d_settings = $options['#d_settings'];
      if (!$this->inBundle($d_settings['bundles'])) {
        continue;
      }
      // Add widgets of different types.
      $value = $config->$key ?? '';
      $type = $d_settings['subtype'] ?? $options['#type'];
      switch ($type) {
        case 'css':
          $classes = preg_split("/[\s,]+/", $value, -1, PREG_SPLIT_NO_EMPTY);
          foreach ($options['modifiers'] as $class => $modifier) {
            if (!$this->inBundle($modifier['#d_settings']['bundles'])) {
              continue;
            }

            $class_key = array_search($class, $classes);
            $default_value = (int) ($class_key !== FALSE);

            if ($default_value) {
              unset($classes[$class_key]);
            }

            $element[$class] = ['#default_value' => $default_value] + $modifier;

            $element[$class]['#attributes'] = [
              'data-modifier' => $class,
            ];

            if ($modifier['#type'] == 'select') {
              $default_select_value = $modifier['#default'] ?? key($modifier['#options']);

              foreach ($modifier['#options'] as $theme_class => $data) {
                $theme_class_key = array_search($theme_class, $classes);
                if ($theme_class_key !== FALSE) {
                  $default_select_value = $theme_class;
                  unset($classes[$theme_class_key]);
                }
              }
              $element[$class]['#default_value'] = $default_select_value;
            }
          }

          $element[$key] = [
            '#default_value' => implode(' ', $classes),
          ];
          break;

        case 'select':
          $element[$key] = [
            '#default_value' => empty($value) ? $options['#default_value'] : $value,
          ] + $options;
          break;

        case 'number':
          $element[$key] = [
            '#default_value' => !empty($value) && $value !== '' ? $value : $options['#default_value'],
            '#min' => $element[$key]['#min'] ?? NULL,
            '#max' => $element[$key]['#max'] ?? NULL,
          ] + $options;
          break;

        default:
          $element[$key] = [
            '#size' => $element[$key]['#size'] ?? 32,
            '#default_value' => $value,
          ] + $options;
      }

      if ($element['#required']) {
        $element[$key]['#required'] = TRUE;
      }
    }

    $tree = $element['#field_parents'];
    $tree[] = $field_name;
    $selector_string = array_shift($tree);
    if (!empty($tree)) {
      foreach ($tree as $item) {
        $selector_string .= "[$item]";
      }
    }

    $element['background-theme-custom'] = [
      '#type' => 'd_color',
      '#title' => 'Background color',
      '#default_value' => isset($config->custom_theme_colors) ? $config->custom_theme_colors->background : '#ffffff',
      '#weight' => 101,
      '#states' => [
        'visible' => [
          ':input[name="' . $selector_string . '[0][value][paragraph-theme]"]' => [
            'value' => 'theme-custom',
          ],
        ],
      ],
    ];

    $element['text-theme-custom'] = [
      '#type' => 'd_color',
      '#title' => 'Text color',
      '#default_value' => isset($config->custom_theme_colors) ? $config->custom_theme_colors->text : '#000000',
      '#weight' => 102,
      '#states' => [
        'visible' => [
          ':input[name="' . $selector_string . '[0][value][paragraph-theme]"]' => [
            'value' => 'theme-custom',
          ],
        ],
      ],
    ];

    return ['value' => $element];
  }

  /**
   * Validate the fields and convert them into a single value as json.
   *
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {
    $values = [];
    $config_options = self::getConfigOptions();
    foreach ($config_options as $key => $options) {
      $value = $form_state->getValue(array_merge($element['#parents'], [$key]));
      $d_settings = $options['#d_settings'];
      if (!$this->inBundle($d_settings['bundles'])) {
        continue;
      }
      $type = $d_settings['subtype'] ?? $options['#type'];
      if ($type === 'css') {
        $classes = preg_split("/[\s,]+/", $value, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($options['modifiers'] as $class => $modifier) {
          $modifier_value = $element[$class]['#value'] ?? NULL;
          if ($modifier_value && $this->inBundle($modifier['#d_settings']['bundles'])) {
            switch ($modifier['#type']) {
              case 'select':
                $classes[] = $modifier_value;
                break;

              default:
                $classes[] = $class;
                break;
            }
          }
        }
        $classes = array_unique($classes);
        $values[$key] = implode(' ', $classes);
      }
      else {
        $values[$key] = $value;
      }
    }
    if ($element['paragraph-theme']['#value'] === 'theme-custom') {
      $values['custom_theme_colors'] = [
        'background' => $element['background-theme-custom']['#value'],
        'text' => $element['text-theme-custom']['#value'],
      ];
    }
    $form_state->setValueForElement($element, json_encode($values));
  }

  /**
   * Returns default options for select fields.
   */
  public static function getModifierDefaults() {
    $modifiers = self::getConfigOptions()[self::CSS_CLASS_SETTING_NAME]['modifiers'];
    $defaults = [];
    foreach ($modifiers as $key => $modifier) {
      if (!empty($modifier['#type']) && $modifier['#type'] === 'select') {
        if (isset($modifier['#default_value']) && $modifier['#default_value']) {
          $default = $modifier['#default_value'];
        }
        else {
          $default = key($modifier['#options']);
        }
        $defaults[] = [
          'options' => array_keys($modifier['#options']),
          'default' => $default,
        ];
      }
    }
    return $defaults;
  }

  /**
   * Get the default value for config option.
   *
   * @param string $option_name
   *   Config option name.
   *
   * @return mixed|null
   *   Option default value, null if not found.
   */
  public static function getConfigOptionDefaultValue(string $option_name) {
    $config = self::getConfigOptions();

    return $config[$option_name]['#default_value'] ?? NULL;
  }

}
