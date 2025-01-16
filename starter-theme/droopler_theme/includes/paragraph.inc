<?php

/**
 * @file
 * Theme and preprocess functions for paragraphs.
 */

declare(strict_types = 1);

use Drupal\Component\Serialization\Json;
use Drupal\Core\Template\Attribute;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function droopler_theme_preprocess_paragraph(array &$variables): void {
  if (isset($variables['attributes'])) {
    $variables['paragraph_attributes'] = is_array($variables['attributes']) ? new Attribute($variables['attributes']) : $variables['attributes'];
  }
  else {
    $variables['paragraph_attributes'] = new Attribute();
  }
  $attributes = [];

  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];
  $parent = $paragraph->getParentEntity();
  $bundle = $paragraph->bundle();
  $paragraph_id = 'paragraph-' . $bundle . '-' . $paragraph->id();
  $variables['paragraph_attributes']->setAttribute('id', $paragraph_id);

  _set_paragraph_attributes_setting_classes($variables);
  _set_paragraph_column_count_settings($variables);
  _set_paragraph_theme_settings($variables, $attributes);
  _set_paragraph_carousel_settings($variables);

  // Text align.
  $variables['paragraph']->textAlign = $paragraph->hasField('field_text_align') && !$paragraph->get('field_text_align')->isEmpty()
    ? $paragraph->get('field_text_align')->getString()
    : NULL;

  // Tiles side.
  $variables['paragraph']->tilesSide = $paragraph->hasField('field_tiles_gallery_side') && !$paragraph->get('field_tiles_gallery_side')->isEmpty()
    ? $paragraph->get('field_tiles_gallery_side')->getString()
    : NULL;

  // Embed side.
  $variables['paragraph']->getEmbedSide = $paragraph->hasField('field_embed_side') && !$paragraph->get('field_embed_side')->isEmpty()
    ? $paragraph->get('field_embed_side')->getString()
    : NULL;

  // Image side.
  $variables['paragraph']->imageSide = $paragraph->hasField('field_image_side') && !$paragraph->get('field_image_side')->isEmpty()
    ? $paragraph->get('field_image_side')->getString()
    : NULL;

  // Image width.
  $variables['paragraph']->imageWidth = $paragraph->hasField('field_image_width') && !$paragraph->get('field_image_width')->isEmpty()
    ? $paragraph->get('field_image_width')->getString()
    : NULL;

  // Form layout.
  $variables['paragraph']->getFormLayout = $paragraph->hasField('field_form_layout') && !$paragraph->get('field_form_layout')->isEmpty()
    ? $paragraph->get('field_form_layout')->getString()
    : NULL;

  // Full width.
  $variables['paragraph']->fullWidth = $paragraph->hasField('field_full_width') && !$paragraph->get('field_full_width')->isEmpty()
    ? (bool) $paragraph->get('field_full_width')->getString()
    : NULL;

  // Full width switch.
  $variables['paragraph']->fullWidthSwitch = $paragraph->hasField('field_d_p_blog_image_full_width') && !$paragraph->get('field_d_p_blog_image_full_width')->isEmpty()
    ? (bool) $paragraph->get('field_d_p_blog_image_full_width')->getString()
    : NULL;

  // Enable grid.
  $variables['paragraph']->enabledGrid = $paragraph->hasField('field_enable_grid') && !$paragraph->get('field_enable_grid')->isEmpty()
    ? (bool) $paragraph->get('field_enable_grid')->getString()
    : NULL;

  // Enable tiles.
  $variables['paragraph']->enabledTiles = $paragraph->hasField('field_enable_tiles') && !$paragraph->get('field_enable_tiles')->isEmpty()
    ? (bool) $paragraph->get('field_enable_tiles')->getString()
    : NULL;

  // Paragraph header in two columns.
  $variables['paragraph']->headerIntoColumns = $paragraph->hasField('field_two_col_header') && !$paragraph->get('field_two_col_header')->isEmpty()
    ? (bool) $paragraph->get('field_two_col_header')->getString()
    : NULL;

  // Add Dividers.
  $variables['paragraph']->hasDividers = $paragraph->hasField('field_add_dividers') && !$paragraph->get('field_add_dividers')->isEmpty()
    ? (bool) $paragraph->get('field_add_dividers')->getString()
    : NULL;

  // Left side content.
  $variables['paragraph']->leftSideContent = $paragraph->hasField('field_left_side_content') && !$paragraph->get('field_left_side_content')->isEmpty()
    ? (bool) $paragraph->get('field_left_side_content')->getString()
    : NULL;

  // Enable price.
  $variables['paragraph']->enabledPrice = $paragraph->hasField('field_enable_price') && !$paragraph->get('field_enable_price')->isEmpty()
    ? (bool) $paragraph->get('field_enable_price')->getString()
    : NULL;

  // Show the price in the sidebar	.
  $variables['paragraph']->priceInSidebar = $paragraph->hasField('field_price_in_sidebar') && !$paragraph->get('field_price_in_sidebar')->isEmpty()
    ? (bool) $paragraph->get('field_price_in_sidebar')->getString()
    : NULL;

  // Media background.
  $variables['paragraph']->mediaBackground = $paragraph->hasField('field_d_media_background') && !$paragraph->get('field_d_media_background')->isEmpty();

  // Paragraph link.
  $paragraph_link = $paragraph->hasField('field_d_cta_single_link') && !$paragraph->get('field_d_cta_single_link')->isEmpty()
    ? $paragraph->get('field_d_cta_single_link')->first()
    : NULL;
  if ($paragraph_link instanceof LinkItem) {
    $variables['paragraph']->link = $paragraph_link->getUrl()->toString();
  }

  if ($parent instanceof ParagraphInterface) {
    // Parent enabled grid.
    $variables['paragraph']->parentEnabledGrid = $parent->hasField('field_enable_grid') && !$parent->get('field_enable_grid')->isEmpty()
      ? (bool) $parent->get('field_enable_grid')->getString()
      : NULL;

    // Parent enabled tiles.
    $variables['paragraph']->parentEnabledTiles = $parent->hasField('field_enable_tiles') && !$parent->get('field_enable_tiles')->isEmpty()
      ? (bool) $parent->get('field_enable_tiles')->getString()
      : NULL;

    // parentColumnSettings.
    _set_paragraph_column_count_settings($variables, $parent);
  }

  $variables['attributes'] = new Attribute($attributes);
}

/**
 * Set paragraph attributes setting classes.
 *
 * @param array $variables
 *  Variables.
 */
function _set_paragraph_attributes_setting_classes(array &$variables): void {
  $setting_fields = [
    'field_margin_top',
    'field_margin_bottom',
    'field_padding_top',
    'field_padding_bottom',
  ];

  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];

  foreach ($setting_fields as $setting_field) {
    if ($paragraph->hasField($setting_field) && !$paragraph->get($setting_field)->isEmpty()) {
      $setting_value = $paragraph->get($setting_field)->getString();
      $variables['paragraph_attributes']->addClass(str_replace('_', '-', $setting_value));
    }
  }

  // Additional classes for paragraphs
  $additional_classes = $paragraph->hasField('field_additional_classes') && !$paragraph->get('field_additional_classes')->isEmpty()
    ? explode(' ', $paragraph->get('field_additional_classes')->getString())
    : NULL;

  if (!empty($additional_classes)) {
    $variables['paragraph_attributes']->addClass($additional_classes);
  }
}

/**
 * Set paragraph column count settings.
 *
 * @param array $variables
 *  Variables.
 * @param \Drupal\paragraphs\Entity\Paragraph|null $parent
 *  Parent paragraph.
 */
function _set_paragraph_column_count_settings(array &$variables, ParagraphInterface|null $parent = NULL): void {
  $column_count_fields = [
    'field_column_count_mobile',
    'field_column_count_tablet',
    'field_column_count_desktop',
  ];

  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $parent ?? $variables['paragraph'];

  $column_count = [];
  foreach ($column_count_fields as $column_count_field) {
    if ($paragraph->hasField($column_count_field) && !$paragraph->get($column_count_field)->isEmpty()) {
      $column_count[str_replace('field_', '', $column_count_field)] = $paragraph->get($column_count_field)->getString();
    }
  }

  if (!empty($column_count)) {
    if ($parent) {
      $variables['paragraph']->parentColumnSettings = $column_count;
    }
    else {
      $variables['paragraph']->columnSettings = $column_count;
    }
  }
}

/**
 * Set paragraph theme settings.
 *
 * @param array $variables
 *  Variables.
 * @param array $attributes
 *  Attributes.
 */
function _set_paragraph_theme_settings(array &$variables, array &$attributes): void {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];

  $theme_settings = $paragraph->hasField('field_theme') && !$paragraph->get('field_theme')->isEmpty()
    ? $paragraph->get('field_theme')->getString()
    : NULL;

  if ($theme_settings) {
    $attributes['data-theme'] = $theme_settings;
  }

  if ($theme_settings === 'theme-custom') {
    $background_color = $paragraph->hasField('field_background_theme_custom') && !$paragraph->get('field_background_theme_custom')->isEmpty()
      ? $paragraph->get('field_background_theme_custom')->getString()
      : NULL;

    if ($background_color) {
      $attributes['style'][] = 'background-color: ' . $background_color . ';';
    }

    $text_color = $paragraph->hasField('field_text_theme_custom') && !$paragraph->get('field_text_theme_custom')->isEmpty()
      ? $paragraph->get('field_text_theme_custom')->getString()
      : NULL;

    if ($text_color) {
      $attributes['style'][] = 'color: ' . $text_color . ';';
    }
  }
}

/**
 * Set paragraph carousel settings.
 *
 * @param array $variables
 *  Variables.
 */
function _set_paragraph_carousel_settings(array &$variables): void {
  $variables['paragraph']->carouselSettings = Json::encode([
    'infinite' => TRUE,
    'slidesToShow' => $variables['paragraph']->columnSettings['column_count_desktop'] ?? 1,
    'slidesToScroll' => 1,
    'swipeToSlide' => TRUE,
    'touchMove' => TRUE,
    'autoplay' => TRUE,
    'autoplaySpeed' => 3000,
    'responsive' => [
      [
        'breakpoint' => _get_mobile_breakpoint_size(),
        'settings' => [
          'arrows' => TRUE,
          'slidesToShow' => $variables['paragraph']->columnSettings['column_count_mobile'] ?? 1,
        ],
      ],
      [
        'breakpoint' => _get_tablet_breakpoint_size(),
        'settings' => [
          'arrows' => TRUE,
          'slidesToShow' => $variables['paragraph']->columnSettings['column_count_tablet'] ?? 1,
        ],
      ],
    ],
  ]);
}

/**
 * Get mobile breakpoint size.
 *
 * @return int
 *   Mobile breakpoint size.
 */
function _get_mobile_breakpoint_size(): int {
  $theme_mobile_breakpoint_value = theme_get_setting('mobile_breakpoint') ?? theme_get_setting('mobile_breakpoint', 'droopler_theme') ?? 540;
  return (int) $theme_mobile_breakpoint_value;
}

/**
 * Get tablet breakpoint size.
 *
 * @return int
 *   Tablet breakpoint size.
 */
function _get_tablet_breakpoint_size(): int {
  $theme_tablet_breakpoint_value = theme_get_setting('tablet_breakpoint') ?? theme_get_setting('tablet_breakpoint', 'droopler_theme') ?? 992;
  return (int) $theme_tablet_breakpoint_value;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function droopler_theme_preprocess_paragraph__d_p_reference_content(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];
  $variables['d_p_reference_content_wrapper_attributes'] = new Attribute();

  // Current manually values.
  $reference_content = $paragraph->get('field_d_p_reference_content');
  $values = $reference_content->getValue();

  // Get latest blog node nids.
  // Selected nodes are excluded from results.
  $available_content_types = _get_content_types();
  $auto_values = _get_sorted_content_by_type($available_content_types, 'created', 'DESC', $values);
  $merged_values = array_merge($values, $auto_values);

  // Delete unpublished content.
  $merged_values = _get_published_content($merged_values);

  // Get max number of values.
  /** @var \Drupal\field\Entity\FieldConfig $def */
  $def = $reference_content->getDataDefinition();
  $cardinality = $def->getFieldStorageDefinition()->getCardinality();
  $min = min(count($merged_values), $cardinality);

  // Build an array with new values.
  $new_values = [];
  for ($i = 0; $i < $min; $i++) {
    $new_values[] = $merged_values[$i];
  }

  // Replace content.
  _replace_content($variables, 'node', 'reference_content', 'field_d_p_reference_content', $new_values);

  $variables['#cache']['tags'][] = 'node_list';
}
