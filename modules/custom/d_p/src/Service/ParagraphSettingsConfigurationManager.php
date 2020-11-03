<?php

namespace Drupal\d_p\Service;

use Drupal\d_p\ParagraphSettingTypesInterface;
use Drupal\d_p\Validation\ParagraphSettingsValidation;

/**
 * Provides manager for paragraphs settings configuration.
 *
 * @package Drupal\d_p\Service
 */
class ParagraphSettingsConfigurationManager implements ParagraphSettingsConfigurationInterface {

  /**
   * {@inheritdoc}
   */
  public function load(string $id): array {
    return $this->getStorage()[$id] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(): array {
    return $this->getStorage();
  }

  /**
   * Getter for paragraph settings configuration storage.
   *
   * @todo: We shouldn't keep it this way. We may think of rewriting
   *    each item into stadalone plugin.
   *
   * @return array[]
   *   Paragraph settings configuration.
   */
  protected function getStorage(): array {
    return [
      ParagraphSettingTypesInterface::CSS_CLASS_SETTING_NAME => [
        '#type' => 'textfield',
        '#title' => t('Additional classes for the paragraph'),
        '#description' => t('Please separate multiple classes by spaces.'),
        '#size' => 32,
        '#d_settings' => [
          'subtype' => 'css',
          'bundles' => [
            'paragraph' => [
              'all',
            ],
          ],
        ],
        'modifiers' => [
          'full-width' => [
            '#title' => t('Full width'),
            '#type' => 'checkbox',
            '#description' => t('Stretch this paragraph to 100% browser width.'),
            '#weight' => 0,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => [
                  'd_p_group_of_text_blocks',
                  'd_p_carousel',
                  'd_p_block',
                ],
              ],
            ],
          ],
          'half-transparent' => [
            '#title' => t('Half transparent'),
            '#type' => 'checkbox',
            '#description' => t('Moves the text to the left and adds a transparent overlay.'),
            '#weight' => 10,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => [
                  'd_p_banner',
                ],
              ],
            ],
          ],
          'with-divider' => [
            '#title' => t('Add dividers'),
            '#type' => 'checkbox',
            '#description' => t('Add vertical lines between all elements.'),
            '#weight' => 20,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => [
                  'd_p_carousel',
                ],
              ],
            ],
          ],
          'with-grid' => [
            '#title' => t('Enable grid'),
            '#type' => 'checkbox',
            '#description' => t('Adds a thin grid around all boxes.'),
            '#weight' => 40,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => [
                  'd_p_group_of_text_blocks',
                  'd_p_side_by_side',
                ],
              ],
            ],
          ],
          'tile' => [
            '#title' => t('Turn into tile'),
            '#type' => 'checkbox',
            '#description' => t('Stretch the background and turn the box into tile.'),
            '#weight' => 50,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => [
                  'd_p_single_text_block',
                ],
              ],
            ],
          ],
          'with-tiles' => [
            '#title' => t('Enable tiles'),
            '#type' => 'checkbox',
            '#description' => t('Enables tile view. You have to set all child boxes to tiles by adjusting their settings.'),
            '#weight' => 60,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => [
                  'd_p_group_of_text_blocks',
                ],
              ],
            ],
          ],
          'header-into-columns' => [
            '#title' => t('Paragraph header in two columns'),
            '#type' => 'checkbox',
            '#description' => t('Enable column mode: header on the left and description on the right.'),
            '#weight' => 70,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => [
                  'd_p_group_of_text_blocks',
                ],
              ],
            ],
          ],
          'with-price' => [
            '#title' => t('Enable price'),
            '#type' => 'checkbox',
            '#description' => t('Show a dynamic price on the right, it requires a JS script to connect to a data source.'),
            '#weight' => 80,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => [
                  'd_p_single_text_block',
                ],
              ],
            ],
          ],
          'stripe-sidebar' => [
            '#title' => t('Show the price in the sidebar'),
            '#type' => 'checkbox',
            '#description' => t('Works only if "Enable price" is turned on. Enables a black sidebar on the right.'),
            '#weight' => 90,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => [
                  'd_p_single_text_block',
                ],
              ],
            ],
          ],
          'paragraph-theme' => [
            '#title' => t('Paragraph Theme'),
            '#description' => t('Choose a color theme for this paragraph.'),
            '#type' => 'select',
            '#options' => [
              'theme-default' => t('Default'),
              'theme-primary' => t('Primary'),
              'theme-secondary' => t('Secondary'),
              'theme-gray' => t('Gray'),
              'theme-custom' => t('Custom'),
            ],
            '#weight' => 100,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => ['all'],
              ],
            ],
          ],
          'margin-top' => [
            '#title' => t('Margin Top'),
            '#description' => t('Choose the size of top margin.'),
            '#type' => 'select',
            '#options' => [
              'margin-top-default' => t('Default'),
              'margin-top-small' => t('Small'),
              'margin-top-medium' => t('Medium'),
              'margin-top-big' => t('Big'),
              'margin-top-none' => t('None'),
            ],
            '#weight' => 110,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => static::getSpacingBundles(),
              ],
            ],
          ],
          'margin-bottom' => [
            '#title' => t('Margin Bottom'),
            '#description' => t('Choose the size of bottom margin.'),
            '#type' => 'select',
            '#options' => [
              'margin-bottom-default' => t('Default'),
              'margin-bottom-small' => t('Small'),
              'margin-bottom-medium' => t('Medium'),
              'margin-bottom-big' => t('Big'),
              'margin-bottom-none' => t('None'),
            ],
            '#weight' => 120,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => static::getSpacingBundles(),
              ],
            ],
          ],
          'padding-top' => [
            '#title' => t('Padding Top'),
            '#description' => t('Choose the size of top padding.'),
            '#type' => 'select',
            '#options' => [
              'padding-top-default' => t('Default'),
              'padding-top-small' => t('Small'),
              'padding-top-big' => t('Big'),
              'padding-top-none' => t('None'),
            ],
            '#weight' => 130,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => static::getSpacingBundles(),
              ],
            ],
          ],
          'padding-bottom' => [
            '#title' => t('Padding Bottom'),
            '#description' => t('Choose the size of bottom padding.'),
            '#type' => 'select',
            '#options' => [
              'padding-bottom-default' => t('Default'),
              'padding-bottom-small' => t('Small'),
              'padding-bottom-big' => t('Big'),
              'padding-bottom-none' => t('None'),
            ],
            '#weight' => 140,
            '#d_settings' => [
              'bundles' => [
                'paragraph' => static::getSpacingBundles(),
              ],
            ],
          ],
        ],
      ],
      ParagraphSettingTypesInterface::HEADING_TYPE_SETTING_NAME => [
        '#title' => t('Heading type'),
        '#description' => t('Select the type of heading to use with this paragraph.'),
        '#type' => 'select',
        '#options' => [
          'h1' => t('H1'),
          'h2' => t('H2'),
          'h3' => t('H3'),
          'h4' => t('H4'),
          'h5' => t('H5'),
          'div' => t('Normal text'),
        ],
        '#default_value' => 'h2',
        '#d_settings' => [
          'outside' => TRUE,
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
      ],
      ParagraphSettingTypesInterface::COLUMN_COUNT_SETTING_NAME => [
        '#title' => t('Column count (desktop)'),
        '#description' => t('Select the number of items in one row.'),
        '#type' => 'number',
        '#min' => '1',
        '#max' => '12',
        '#default_value' => '4',
        '#element_validate' => [
          [ParagraphSettingsValidation::class, 'validateColumnCount'],
        ],
        '#d_settings' => [
          'outside' => TRUE,
          'bundles' => [
            'paragraph' => [
              'd_p_carousel',
              'd_p_group_of_counters',
              'd_p_group_of_text_blocks',
            ],
          ],
          'validation' => [
            'column_count' => [
              'allowed_values' => range(1, 12),
              'bundle_allowed_values' => [
                'd_p_group_of_counters' => [1, 2, 3, 4, 6, 12],
                'd_p_group_of_text_blocks' => [1, 2, 3, 4, 6, 12],
              ],
            ],
          ],
        ],
      ],
      ParagraphSettingTypesInterface::COLUMN_COUNT_TABLET_SETTING_NAME => [
        '#title' => t('Column count (tablet)'),
        '#description' => t('Select the number of items in one row.'),
        '#type' => 'number',
        '#min' => '1',
        '#max' => '12',
        '#default_value' => '3',
        '#element_validate' => [
          [ParagraphSettingsValidation::class, 'validateColumnCount'],
        ],
        '#d_settings' => [
          'bundles' => [
            'outside' => TRUE,
            'paragraph' => [
              'd_p_carousel',
              'd_p_group_of_counters',
              'd_p_group_of_text_blocks',
            ],
          ],
          'validation' => [
            'column_count' => [
              'allowed_values' => range(1, 12),
              'bundle_allowed_values' => [
                'd_p_group_of_counters' => [1, 2, 3, 4, 6, 12],
                'd_p_group_of_text_blocks' => [1, 2, 3, 4, 6, 12],
              ],
            ],
          ],
        ],
      ],
      ParagraphSettingTypesInterface::COLUMN_COUNT_MOBILE_SETTING_NAME => [
        '#title' => t('Column count (mobile)'),
        '#description' => t('Select the number of items in one row.'),
        '#type' => 'number',
        '#min' => '1',
        '#max' => '12',
        '#default_value' => '1',
        '#element_validate' => [
          [ParagraphSettingsValidation::class, 'validateColumnCount'],
        ],
        '#d_settings' => [
          'outside' => TRUE,
          'bundles' => [
            'paragraph' => [
              'd_p_carousel',
              'd_p_group_of_counters',
              'd_p_group_of_text_blocks',
            ],
          ],
          'validation' => [
            'column_count' => [
              'allowed_values' => range(1, 12),
              'bundle_allowed_values' => [
                'd_p_group_of_counters' => [1, 2, 3, 4, 6, 12],
                'd_p_group_of_text_blocks' => [1, 2, 3, 4, 6, 12],
              ],
            ],
          ],
        ],
      ],
      ParagraphSettingTypesInterface::PARAGRAPH_SETTING_FORM_LAYOUT => [
        '#title' => t('Form layout'),
        '#description' => t('Choose form layout'),
        '#type' => 'select',
        '#options' => [
          'left' => t('Left'),
          'right' => t('Right'),
          'bottom' => t('Bottom'),
        ],
        '#d_settings' => [
          'bundles' => [
            'paragraph' => [
              'd_p_form',
            ],
          ],
        ],
      ],
      ParagraphSettingTypesInterface::PARAGRAPH_SETTING_EMBED_LAYOUT => [
        '#title' => t('Embed side'),
        '#type' => 'select',
        '#options' => [
          'left' => t('Left'),
          'right' => t('Right'),
          'full' => t('Full width'),
        ],
        '#d_settings' => [
          'bundles' => [
            'paragraph' => [
              'd_p_side_embed',
            ],
          ],
        ],
      ],
      ParagraphSettingTypesInterface::PARAGRAPH_SETTING_SIDE_IMAGE_LAYOUT => [
        '#title' => t('Image side'),
        '#type' => 'select',
        '#options' => [
          'left' => t('Left'),
          'right' => t('Right'),
          'left-wide' => t('Left (wide)'),
          'right-wide' => t('Right (wide)'),
        ],
        '#d_settings' => [
          'bundles' => [
            'paragraph' => [
              'd_p_side_image',
            ],
          ],
        ],
      ],
      ParagraphSettingTypesInterface::PARAGRAPH_SETTING_SIDE_TILES_LAYOUT => [
        '#title' => t('Tiles gallery side'),
        '#type' => 'select',
        '#options' => [
          'left' => t('Left'),
          'right' => t('Right'),
        ],
        '#d_settings' => [
          'bundles' => [
            'paragraph' => [
              'd_p_side_tiles',
            ],
          ],
        ],
      ],
      ParagraphSettingTypesInterface::PARAGRAPH_FEATURED_IMAGES => [
        '#title' => t('Featured images'),
        '#description' => t('Comma separated image numbers. Example: 1,4,7'),
        '#type' => 'textfield',
        '#d_settings' => [
          'outside' => TRUE,
          'bundles' => [
            'paragraph' => [
              'd_p_tiles',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Getter for spacing bundles.
   *
   * @todo We should decide which bundle support spacings
   *    by providing some sort of configuration o paragrpah bundles.
   *
   * @return string[]
   *   Paragraph bundles supporting spacings.
   */
  public static function getSpacingBundles() {
    return [
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
  }

}
