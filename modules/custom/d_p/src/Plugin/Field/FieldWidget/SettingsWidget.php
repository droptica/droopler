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
  const PARAGRAPH_LAYOUT_SETTING = 'layout_class';
  const PARAGRAPH_FEATURED_IMAGES = 'featured_images';

  /**
   * @var array
   *   Stores bundles where spacing settings should be enabled.
   */
  private $spacingBundles = [
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

  private function getConfigOptions() {
    return [
      self::CSS_CLASS_SETTING_NAME => [
        'title' => $this->t('Additional classes for the paragraph'),
        'description' => $this->t('Please separate multiple classes by spaces.'),
        'type' => 'css',
        'bundles' => ['paragraph' => ['all']],
        'modifiers' => [
          'theme-invert' => [
            'title' => $this->t('Inverted colors'),
            'description' => $this->t('Toggle dark and light theme of the paragraph.'),
            'bundles' => [
              'paragraph' => [
                'd_p_banner',
                'd_p_text_paged',
                'd_p_single_text_block',
                'd_p_group_of_text_blocks',
                'd_p_carousel',
                'd_p_side_embed',
                'd_p_side_image',
                'd_p_side_tiles',
              ],
            ],
          ],
          'full-width' => [
            'title' => $this->t('Full width'),
            'description' => $this->t('Stretch this paragraph to 100% browser width.'),
            'bundles' => [
              'paragraph' => [
                'd_p_group_of_text_blocks',
                'd_p_carousel',
                'd_p_block'
              ],
            ],
          ],
          'half-transparent' => [
            'title' => $this->t('Half transparent'),
            'description' => $this->t('Moves the text to the left and adds a transparent overlay.'),
            'bundles' => [
              'paragraph' => [
                'd_p_banner',
              ],
            ],
          ],
          'with-divider' => [
            'title' => $this->t('Add dividers'),
            'description' => $this->t('Add vertical lines between all elements.'),
            'bundles' => [
              'paragraph' => [
                'd_p_carousel',
              ],
            ],
          ],
          'background-gray-light-2' => [
            'title' => $this->t('Gray background'),
            'description' => $this->t('Change paragraph background to light gray.'),
            'bundles' => [
              'paragraph' => [
                'd_p_carousel',
                'd_p_group_of_text_blocks',
                'd_p_reference_content',
                'd_p_text_paged',
              ],
            ],
          ],
          'slider-desktop-off' => [
            'title' => $this->t('Turn off slider on desktop'),
            'description' => $this->t('The slider will be visible only on tablet and mobile devices.'),
            'bundles' => [
              'paragraph' => [
                'd_p_carousel',
              ],
            ],
          ],
          'with-grid' => [
            'title' => $this->t('Enable grid'),
            'description' => $this->t('Adds a thin grid around all boxes.'),
            'bundles' => [
              'paragraph' => [
                'd_p_group_of_text_blocks',
                'd_p_side_by_side',
              ],
            ],
          ],
          'tile' => [
            'title' => $this->t('Turn into tile'),
            'description' => $this->t('Stretch the background and turn the box into tile.'),
            'bundles' => [
              'paragraph' => [
                'd_p_single_text_block',
              ],
            ],
          ],
          'with-tiles' => [
            'title' => $this->t('Enable tiles'),
            'description' => $this->t('Enables tile view. You have to set all child boxes to tiles by adjusting their settings.'),
            'bundles' => [
              'paragraph' => [
                'd_p_group_of_text_blocks',
              ],
            ],
          ],
          'header-into-columns' => [
            'title' => $this->t('Paragraph header in two columns'),
            'description' => $this->t('Enable column mode: header on the left and description on the right.'),
            'bundles' => [
              'paragraph' => [
                'd_p_group_of_text_blocks',
              ],
            ],
          ],
          'with-price' => [
            'title' => $this->t('Enable price'),
            'description' => $this->t('Show a dynamic price on the right, it requires a JS script to connect to a data source.'),
            'bundles' => [
              'paragraph' => [
                'd_p_single_text_block',
              ],
            ],
          ],
          'stripe-sidebar' => [
            'title' => $this->t('Show the price in the sidebar'),
            'description' => $this->t('Works only if "Enable price" is turned on. Enables a black sidebar on the right.'),
            'bundles' => [
              'paragraph' => [
                'd_p_single_text_block',
              ],
            ],
          ],
          'paragraph-theme' => [
            'title' => $this->t('Paragraph Theme'),
            'description' => $this->t('Choose a color theme for this paragraph.'),
            'type' => 'select',
            'options' => [
              'theme-default' => $this->t('Default'),
              'theme-primary' => $this->t('Primary'),
              'theme-secondary' => $this->t('Secondary'),
              'theme-gray' => $this->t('Gray'),
            ],
            'bundles' => [
              'paragraph' => ['all'],
            ],
          ],
          'margin-top' => [
            'title' => $this->t('Margin Top'),
            'description' => $this->t('Choose the size of top margin.'),
            'type' => 'select',
            'options' => [
              'margin-top-default' => $this->t('Default'),
              'margin-top-small' => $this->t('Small'),
              'margin-top-medium' => $this->t('Medium'),
              'margin-top-big' => $this->t('Big'),
              'margin-top-none' => $this->t('None'),
            ],
            'bundles' => [
              'paragraph' => $this->spacingBundles,
            ],
          ],
          'margin-bottom' => [
            'title' => $this->t('Margin Bottom'),
            'description' => $this->t('Choose the size of bottom margin.'),
            'type' => 'select',
            'options' => [
              'margin-bottom-default' => $this->t('Default'),
              'margin-bottom-small' => $this->t('Small'),
              'margin-bottom-medium' => $this->t('Medium'),
              'margin-bottom-big' => $this->t('Big'),
              'margin-bottom-none' => $this->t('None'),
            ],
            'bundles' => [
              'paragraph' => $this->spacingBundles,
            ],
          ],
          'padding-top' => [
            'title' => $this->t('Padding Top'),
            'description' => $this->t('Choose the size of top padding.'),
            'type' => 'select',
            'options' => [
              'padding-top-default' => $this->t('Default'),
              'padding-top-small' => $this->t('Small'),
              'padding-top-big' => $this->t('Big'),
              'padding-top-none' => $this->t('None'),
            ],
            'bundles' => [
              'paragraph' => $this->spacingBundles,
            ],
          ],
          'padding-bottom' => [
            'title' => $this->t('Padding Bottom'),
            'description' => $this->t('Choose the size of bottom padding.'),
            'type' => 'select',
            'options' => [
              'padding-bottom-default' => $this->t('Default'),
              'padding-bottom-small' => $this->t('Small'),
              'padding-bottom-big' => $this->t('Big'),
              'padding-bottom-none' => $this->t('None'),
            ],
            'bundles' => [
              'paragraph' => $this->spacingBundles,
            ],
          ],
        ],
      ],
      self::HEADING_TYPE_SETTING_NAME => [
        'title' => $this->t('Heading type'),
      // The widget is moved outside of field_d_settings form element.
        'outside' => TRUE,
        'description' => $this->t('Select the type of heading to use with this paragraph.'),
        'type' => 'select',
        'options' => [
          'h1' => $this->t('H1'),
          'h2' => $this->t('H2'),
          'h3' => $this->t('H3'),
          'h4' => $this->t('H4'),
          'h5' => $this->t('H5'),
          'div' => $this->t('Normal text'),
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
        'title' => $this->t('Column count'),
        'outside' => TRUE,
        'description' => $this->t('Select the number of items in one row.'),
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
      ],
      self::PARAGRAPH_LAYOUT_SETTING => [
        'title' => $this->t('Paragraph layout settings'),
        'type' => 'layout_css',
        'bundles' => [
          'paragraph' => [
            'd_p_form',
            'd_p_side_embed',
            'd_p_side_image',
            'd_p_side_tiles',
          ],
        ],
        'modifiers' => [
          'form-layout' => [
            'title' => $this->t('Form layout'),
            'description' => $this->t('Choose form layout'),
            'type' => 'select',
            'options' => [
              'left' => $this->t('Left'),
              'right' => $this->t('Right'),
              'bottom' => $this->t('Bottom'),
            ],
            'bundles' => [
              'paragraph' => [
                'd_p_form',
              ],
            ],
          ],
          'embed-layout' => [
            'title' => $this->t('Embed side'),
            'type' => 'select',
            'options' => [
              'left' => $this->t('Left'),
              'right' => $this->t('Right'),
              'full' => $this->t('Full width'),
            ],
            'bundles' => [
              'paragraph' => [
                'd_p_side_embed',
              ],
            ],
          ],
          'side-image-layout' => [
            'title' => $this->t('Image side'),
            'type' => 'select',
            'options' => [
              'left' => $this->t('Left'),
              'right' => $this->t('Right'),
              'left-wide' => $this->t('Left (wide)'),
              'right-wide' => $this->t('Right (wide)'),
            ],
            'bundles' => [
              'paragraph' => [
                'd_p_side_image',
              ],
            ],
          ],
          'side-tiles-layout' => [
            'title' => $this->t('Tiles gallery side'),
            'type' => 'select',
            'options' => [
              'left' => $this->t('Left'),
              'right' => $this->t('Right'),
            ],
            'bundles' => [
              'paragraph' => [
                'd_p_side_tiles',
              ],
            ],
          ],
        ],
      ],
      self::PARAGRAPH_FEATURED_IMAGES => [
        'title' => $this->t('Featured images'),
        'outside' => TRUE,
        'description' => $this->t('Comma separated image numbers. Example: 1,4,7'),
        'type' => 'textfield',
        'bundles' => [
          'paragraph' => [
            'd_p_tiles',
          ],
        ],
      ],
    ];
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
  protected function inBundle($list) {
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

    // Set up the form element for this widget.
    $element += [
      '#type' => 'details',
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    $config_options = $this->getConfigOptions();

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
            } else {
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
              // First element as default.
              $default_select_value = key($modifier['options']);
              foreach ($modifier['options'] as $theme_class => $data) {
                $theme_class_key = array_search($theme_class, $classes);
                if ($theme_class_key !== FALSE) {
                  $default_select_value = $theme_class;
                  unset($classes[$theme_class_key]);
                }
              }
              $element[$class]['#options'] = $modifier['options'];
              $element[$class]['#default_value'] = $default_select_value;
            }
          }

          $element[$key] = [
            '#type' => 'textfield',
            '#title' => $options['title'],
            '#description' => $options['description'] ?? '',
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

        case 'layout_css':
          foreach ($options['modifiers'] as $layout => $modifier) {
            if (!$this->inBundle($modifier['bundles'])) {
              continue;
            }
            $element[$layout] = [
              '#type' => $modifier['type'],
              '#description' => $modifier['description'] ?? '',
              '#title' => $modifier['title'],
              '#options' => $modifier['options'],
              '#default_value' => $value,
              '#attributes' => ['data-layout' => $layout],
              '#required' => TRUE,
            ];
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
      $value = $form_state->getValue(array_merge($element['#parents'], [$key]));
      if (!$this->inBundle($options['bundles'])) {
        continue;
      }

      switch ($options['type']) {
        case 'css':
        case 'layout_css':
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
          $values[$key] = join(' ', $classes);
          break;

        default:
          $values[$key] = $value;
      }
    }
    $form_state->setValueForElement($element, json_encode($values));
  }

}
