<?php

namespace Drupal\d_p;

/**
 * Provides interface for setting types.
 *
 * @package Drupal\d_p
 */
interface ParagraphSettingTypesInterface {

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

  const THEME_COLORS_SETTING_NAME = 'custom_theme_colors';

}
