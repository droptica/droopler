<?php

declare(strict_types=1);

namespace Drupal\d_p;

/**
 * Provides interface for setting types.
 *
 * Setting names (machine names of `field_d_settings` keys) are exposed as
 * typed class constants so consumers (paragraph hooks, formatters, twig
 * preprocessors) can rely on a stable, type-checked surface instead of bare
 * strings.
 */
interface ParagraphSettingTypesInterface {

  public const string CSS_CLASS_SETTING_NAME = 'custom_class';

  public const string HEADING_TYPE_SETTING_NAME = 'heading_type';

  public const string COLUMN_COUNT_SETTING_NAME = 'column_count';

  public const string COLUMN_COUNT_MOBILE_SETTING_NAME = 'column_count_mobile';

  public const string COLUMN_COUNT_TABLET_SETTING_NAME = 'column_count_tablet';

  public const string PARAGRAPH_FEATURED_IMAGES = 'featured_images';

  public const string PARAGRAPH_SETTING_FORM_LAYOUT = 'form_layout';

  public const string PARAGRAPH_SETTING_EMBED_LAYOUT = 'embed_layout';

  public const string PARAGRAPH_SETTING_SIDE_IMAGE_LAYOUT = 'side_image_layout';

  public const string PARAGRAPH_SETTING_SIDE_TILES_LAYOUT = 'side_tiles_layout';

  public const string THEME_COLORS_SETTING_NAME = 'custom_theme_colors';

}
