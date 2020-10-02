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
  const COLUMN_COUNT_SETTING_NAME = 'column_count';
  const COLUMN_COUNT_MOBILE_SETTING_NAME = 'column_count_mobile';
  const COLUMN_COUNT_TABLET_SETTING_NAME = 'column_count_tablet';
  const PARAGRAPH_FEATURED_IMAGES = 'featured_images';
  const PARAGRAPH_SETTING_FORM_LAYOUT = 'form_layout';
  const PARAGRAPH_SETTING_EMBED_LAYOUT = 'embed_layout';
  const PARAGRAPH_SETTING_SIDE_IMAGE_LAYOUT = 'side_image_layout';
  const PARAGRAPH_SETTING_SIDE_TILES_LAYOUT = 'side_tiles_layout';

  /**
   * List of bundles for which we validate column count field.
   *
   * @var array
   */
  private static $columnCountValidateBundles = ['d_p_group_of_text_blocks', 'd_p_group_of_counters', 'd_p_carousel'];

  /**
   * List of bundles.
   *
   * @var array
   *   Stores bundles where spacing settings should be enabled.
   */
  private static $spacingBundles = [
    'd_p_banner',
    'd_p_block',
    'd_p_blog_image',
    'd_p_blog_text',
    'd_p_carousel',
    'd_p_form',
    'd_p_gallery',
    'd_p_group_of_counters',
    'd_p_group_of_text_blocks',
    'd_p_reference_content',
    'd_p_side_embed',
    'd_p_side_image',
    'd_p_side_tiles',
    'd_p_side_by_side',
    'd_p_subscribe_file',
    'd_p_text_paged',
    'd_p_text_with_bckg',
    'd_p_tiles',
  ];

  /**
   * Get configuration options for fields in paragraph settings.
   */
  private static function getConfigOptions() {
    $form = [
      self::CSS_CLASS_SETTING_NAME => [
        'title' => t('Additional classes for the paragraph'),
        'description' => t('Please separate multiple classes by spaces.'),
        'type' => 'css',
        'bundles' => ['paragraph' => ['all']],
        'modifiers' => [
          'full-width' => [
            'title' => t('Full width'),
            'description' => t('Stretch this paragraph to 100% browser width.'),
            'weight' => 0,
            'bundles' => [
              'paragraph' => [
                'd_p_group_of_text_blocks',
                'd_p_carousel',
                'd_p_block',
              ],
            ],
          ],
          'half-transparent' => [
            'title' => t('Half transparent'),
            'description' => t('Moves the text to the left and adds a transparent overlay.'),
            'weight' => 10,
            'bundles' => [
              'paragraph' => [
                'd_p_banner',
              ],
            ],
          ],
          'with-divider' => [
            'title' => t('Add dividers'),
            'description' => t('Add vertical lines between all elements.'),
            'weight' => 20,
            'bundles' => [
              'paragraph' => [
                'd_p_carousel',
              ],
            ],
          ],
          'slider-desktop-off' => [
            'title' => t('Turn off slider on desktop'),
            'description' => t('The slider will be visible only on tablet and mobile devices.'),
            'weight' => 30,
            'bundles' => [
              'paragraph' => [
                'd_p_carousel',
              ],
            ],
          ],
          'with-grid' => [
            'title' => t('Enable grid'),
            'description' => t('Adds a thin grid around all boxes.'),
            'weight' => 40,
            'bundles' => [
              'paragraph' => [
                'd_p_group_of_text_blocks',
                'd_p_side_by_side',
              ],
            ],
          ],
          'tile' => [
            'title' => t('Turn into tile'),
            'description' => t('Stretch the background and turn the box into tile.'),
            'weight' => 50,
            'bundles' => [
              'paragraph' => [
                'd_p_single_text_block',
              ],
            ],
          ],
          'with-tiles' => [
            'title' => t('Enable tiles'),
            'description' => t('Enables tile view. You have to set all child boxes to tiles by adjusting their settings.'),
            'weight' => 60,
            'bundles' => [
              'paragraph' => [
                'd_p_group_of_text_blocks',
              ],
            ],
          ],
          'header-into-columns' => [
            'title' => t('Paragraph header in two columns'),
            'description' => t('Enable column mode: header on the left and description on the right.'),
            'weight' => 70,
            'bundles' => [
              'paragraph' => [
                'd_p_group_of_text_blocks',
              ],
            ],
          ],
          'with-price' => [
            'title' => t('Enable price'),
            'description' => t('Show a dynamic price on the right, it requires a JS script to connect to a data source.'),
            'weight' => 80,
            'bundles' => [
              'paragraph' => [
                'd_p_single_text_block',
              ],
            ],
          ],
          'stripe-sidebar' => [
            'title' => t('Show the price in the sidebar'),
            'description' => t('Works only if "Enable price" is turned on. Enables a black sidebar on the right.'),
            'weight' => 90,
            'bundles' => [
              'paragraph' => [
                'd_p_single_text_block',
              ],
            ],
          ],
          'paragraph-theme' => [
            'title' => t('Paragraph Theme'),
            'description' => t('Choose a color theme for this paragraph.'),
            'type' => 'select',
            'options' => [
              'theme-default' => t('Default'),
              'theme-primary' => t('Primary'),
              'theme-secondary' => t('Secondary'),
              'theme-gray' => t('Gray'),
              'theme-custom' => t('Custom'),
            ],
            'weight' => 100,
            'bundles' => [
              'paragraph' => ['all'],
            ],
          ],
          'margin-top' => [
            'title' => t('Margin Top'),
            'description' => t('Choose the size of top margin.'),
            'type' => 'select',
            'options' => [
              'margin-top-default' => t('Default'),
              'margin-top-small' => t('Small'),
              'margin-top-medium' => t('Medium'),
              'margin-top-big' => t('Big'),
              'margin-top-none' => t('None'),
            ],
            'weight' => 110,
            'bundles' => [
              'paragraph' => self::$spacingBundles,
            ],
          ],
          'margin-bottom' => [
            'title' => t('Margin Bottom'),
            'description' => t('Choose the size of bottom margin.'),
            'type' => 'select',
            'options' => [
              'margin-bottom-default' => t('Default'),
              'margin-bottom-small' => t('Small'),
              'margin-bottom-medium' => t('Medium'),
              'margin-bottom-big' => t('Big'),
              'margin-bottom-none' => t('None'),
            ],
            'weight' => 120,
            'bundles' => [
              'paragraph' => self::$spacingBundles,
            ],
          ],
          'padding-top' => [
            'title' => t('Padding Top'),
            'description' => t('Choose the size of top padding.'),
            'type' => 'select',
            'options' => [
              'padding-top-default' => t('Default'),
              'padding-top-small' => t('Small'),
              'padding-top-big' => t('Big'),
              'padding-top-none' => t('None'),
            ],
            'weight' => 130,
            'bundles' => [
              'paragraph' => self::$spacingBundles,
            ],
          ],
          'padding-bottom' => [
            'title' => t('Padding Bottom'),
            'description' => t('Choose the size of bottom padding.'),
            'type' => 'select',
            'options' => [
              'padding-bottom-default' => t('Default'),
              'padding-bottom-small' => t('Small'),
              'padding-bottom-big' => t('Big'),
              'padding-bottom-none' => t('None'),
            ],
            'weight' => 140,
            'bundles' => [
              'paragraph' => self::$spacingBundles,
            ],
          ],
        ],
      ],
      self::HEADING_TYPE_SETTING_NAME => [
        'title' => t('Heading type'),
      // The widget is moved outside of field_d_settings form element.
        'outside' => TRUE,
        'description' => t('Select the type of heading to use with this paragraph.'),
        'type' => 'select',
        'options' => [
          'h1' => t('H1'),
          'h2' => t('H2'),
          'h3' => t('H3'),
          'h4' => t('H4'),
          'h5' => t('H5'),
          'div' => t('Normal text'),
        ],
        'default' => 'h2',
        'bundles' => [
          'paragraph' => [
            'd_p_banner',
            'd_p_carousel',
            'd_p_carousel_item',
            'd_p_form',
            'd_p_gallery',
            'd_p_group_of_counters',
            'd_p_group_of_text_blocks',
            'd_p_node',
            'd_p_reference_content',
            'd_p_side_embed',
            'd_p_side_image',
            'd_p_side_tiles',
            'd_p_single_text_block',
            'd_p_subscribe_file',
            'd_p_text_paged',
            'd_p_text_with_bckg',
            'd_p_tiles',
          ],
        ],
      ],
      self::COLUMN_COUNT_SETTING_NAME => [
        'title' => t('Column count (desktop)'),
        'outside' => TRUE,
        'description' => t('Select the number of items in one row.'),
        'type' => 'number',
        'min' => '1',
        'max' => '12',
        'default' => '4',
        'bundles' => [
          'paragraph' => [
            'd_p_carousel',
            'd_p_group_of_counters',
            'd_p_group_of_text_blocks',
          ],
        ],
        'validation' => [
          'default' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
          'paragraphs' => [
            'd_p_group_of_counters' => [1, 2, 3, 4, 6, 12],
            'd_p_group_of_text_blocks' => [1, 2, 3, 4, 6, 12],
          ],
        ],
      ],
      self::COLUMN_COUNT_TABLET_SETTING_NAME => [
        'title' => t('Column count (tablet)'),
        'outside' => TRUE,
        'description' => t('Select the number of items in one row.'),
        'type' => 'number',
        'min' => '1',
        'max' => '12',
        'default' => '3',
        'bundles' => [
          'paragraph' => [
            'd_p_carousel',
            'd_p_group_of_counters',
            'd_p_group_of_text_blocks',
          ],
        ],
        'validation' => [
          'default' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
          'paragraphs' => [
            'd_p_group_of_counters' => [1, 2, 3, 4, 6, 12],
            'd_p_group_of_text_blocks' => [1, 2, 3, 4, 6, 12],
          ],
        ],
      ],
      self::COLUMN_COUNT_MOBILE_SETTING_NAME => [
        'title' => t('Column count (mobile)'),
        'outside' => TRUE,
        'description' => t('Select the number of items in one row.'),
        'type' => 'number',
        'min' => '1',
        'max' => '12',
        'default' => '1',
        'bundles' => [
          'paragraph' => [
            'd_p_carousel',
            'd_p_group_of_counters',
            'd_p_group_of_text_blocks',
          ],
        ],
        'validation' => [
          'default' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
          'paragraphs' => [
            'd_p_group_of_counters' => [1, 2, 3, 4, 6, 12],
            'd_p_group_of_text_blocks' => [1, 2, 3, 4, 6, 12],
          ],
        ],
      ],
      self::PARAGRAPH_SETTING_FORM_LAYOUT => [
        'title' => t('Form layout'),
        'description' => t('Choose form layout'),
        'type' => 'select',
        'options' => [
          'left' => t('Left'),
          'right' => t('Right'),
          'bottom' => t('Bottom'),
        ],
        'bundles' => [
          'paragraph' => [
            'd_p_form',
          ],
        ],
      ],
      self::PARAGRAPH_SETTING_EMBED_LAYOUT => [
        'title' => t('Embed side'),
        'type' => 'select',
        'options' => [
          'left' => t('Left'),
          'right' => t('Right'),
          'full' => t('Full width'),
        ],
        'bundles' => [
          'paragraph' => [
            'd_p_side_embed',
          ],
        ],
      ],
      self::PARAGRAPH_SETTING_SIDE_IMAGE_LAYOUT => [
        'title' => t('Image side'),
        'type' => 'select',
        'options' => [
          'left' => t('Left'),
          'right' => t('Right'),
          'left-wide' => t('Left (wide)'),
          'right-wide' => t('Right (wide)'),
        ],
        'bundles' => [
          'paragraph' => [
            'd_p_side_image',
          ],
        ],
      ],
      self::PARAGRAPH_SETTING_SIDE_TILES_LAYOUT => [
        'title' => t('Tiles gallery side'),
        'type' => 'select',
        'options' => [
          'left' => t('Left'),
          'right' => t('Right'),
        ],
        'bundles' => [
          'paragraph' => [
            'd_p_side_tiles',
          ],
        ],
      ],
      self::PARAGRAPH_FEATURED_IMAGES => [
        'title' => t('Featured images'),
        'outside' => TRUE,
        'description' => t('Comma separated image numbers. Example: 1,4,7'),
        'type' => 'textfield',
        'bundles' => [
          'paragraph' => [
            'd_p_tiles',
          ],
        ],
      ],
    ];

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
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $config = !empty($value) ? json_decode($value) : [];
    $field_name = $items->getFieldDefinition()->getName();

    // Set up the form element for this widget.
    $element += [
      '#type' => 'fieldset',
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    if (in_array($this->fieldDefinition->getTargetBundle(), self::$columnCountValidateBundles)) {
      array_unshift($element['#element_validate'], [$this, 'validateColumnCount']);
    }

    $config_options = self::getConfigOptions();
    foreach ($config_options as $key => $options) {
      // If the widget is not available in the current bundle, just skip it.
      if (!$this->inBundle($options['bundles'])) {
        continue;
      }
      // Add widgets of different types.
      $value = $config->$key ?? '';
      switch ($options['type']) {
        case 'css':
          $classes = preg_split("/[\s,]+/", $value, -1, PREG_SPLIT_NO_EMPTY);
          foreach ($options['modifiers'] as $class => $modifier) {
            if (!$this->inBundle($modifier['bundles'])) {
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

            $element_type = !empty($modifier['type']) ? $modifier['type'] : 'checkbox';
            $element[$class] = [
              '#type' => $element_type,
              '#description' => $modifier['description'] ?? '',
              '#title' => $modifier['title'],
              '#default_value' => $default_value,
              '#attributes' => ['data-modifier' => $class],
            ];
            if ($modifier['type'] == 'select') {
              if ($modifier['default']) {
                $default_select_value = $modifier['default'];
              }
              else {
                // First element as default.
                $default_select_value = key($modifier['options']);
              }
              foreach ($modifier['options'] as $theme_class => $data) {
                $theme_class_key = array_search($theme_class, $classes);
                if ($theme_class_key !== FALSE) {
                  $default_select_value = $theme_class;
                  unset($classes[$theme_class_key]);
                }
              }
              $element[$class]['#options'] = $modifier['options'];
              $element[$class]['#default_value'] = $default_select_value;
              $element[$class]['#weight'] = $modifier['weight'] ?? 0;
            }
          }

          $element[$key] = [
            '#type' => 'textfield',
            '#title' => $options['title'],
            '#description' => $options['description'] ?? '',
            '#size' => 32,
            '#default_value' => implode(' ', $classes),
          ];
          if ($element['#required']) {
            $element[$key]['#required'] = TRUE;
          }
          break;

        case 'select':
          $element[$key] = [
            '#type' => 'select',
            '#title' => $options['title'],
            '#description' => $options['description'] ?? '',
            '#options' => $options['options'],
            '#default_value' => empty($value) ? $options['default'] : $value,
          ];
          if ($element['#required']) {
            $element[$key]['#required'] = TRUE;
          }
          break;

        case 'number':
          $element[$key] = [
            '#type' => 'number',
            '#title' => $options['title'],
            '#description' => $options['description'] ?? '',
            '#default_value' => !empty($value) && $value !== '' ? $value : $options['default'],
            '#min' => $options['min'] ?? NULL,
            '#max' => $options['max'] ?? NULL,
          ];
          if ($element['#required']) {
            $element[$key]['#required'] = TRUE;
          }
          break;

        default:
          $element[$key] = [
            '#type' => 'textfield',
            '#title' => $options['title'],
            '#description' => $options['description'] ?? '',
            '#size' => 32,
            '#default_value' => $value,
          ];
          if ($element['#required']) {
            $element[$key]['#required'] = TRUE;
          }
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
      if (!$this->inBundle($options['bundles'])) {
        continue;
      }
      if ($options['type'] === 'css') {
        $classes = preg_split("/[\s,]+/", $value, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($options['modifiers'] as $class => $modifier) {
          $modifier_value = $element[$class]['#value'] ?? NULL;
          if ($modifier_value && $this->inBundle($modifier['bundles'])) {
            switch ($modifier['type']) {
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
   * Validate field column count.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateColumnCount(array $element, FormStateInterface $form_state) {
    $fields = [
      self::COLUMN_COUNT_SETTING_NAME,
      self::COLUMN_COUNT_TABLET_SETTING_NAME,
      self::COLUMN_COUNT_MOBILE_SETTING_NAME,
    ];
    $config_options = self::getConfigOptions();
    $bundle = $this->fieldDefinition->getTargetBundle();
    foreach ($fields as $field) {
      $column_count = $form_state->getValue(array_merge($element['#parents'], [$field]));
      $valid_number_of_columns = isset($config_options[$field]['validation']['paragraphs'][$bundle])
        ? $config_options[$field]['validation']['paragraphs'][$bundle]
        : $config_options[$field]['validation']['default'];

      if (!in_array($column_count, $valid_number_of_columns)) {
        $form_state->setError(
          $element[$field],
          $this->t('The allowed number of columns for @field is @column_number',
            ['@column_number' => implode(', ', $valid_number_of_columns), '@field' => $config_options[$field]['title']]));
      }
    }
  }

  /**
   * Returns default options for select fields.
   */
  public static function getModifierDefaults() {
    $modifiers = self::getConfigOptions()[self::CSS_CLASS_SETTING_NAME]['modifiers'];
    $defaults = [];
    foreach ($modifiers as $key => $modifier) {
      if (!empty($modifier['type']) && $modifier['type'] === 'select') {
        if (isset($modifier['default']) && $modifier['default']) {
          $default = $modifier['default'];
        }
        else {
          $default = key($modifier['options']);
        }
        $defaults[] = [
          'options' => array_keys($modifier['options']),
          'default' => $default,
        ];
      }
    }
    return $defaults;
  }

}
