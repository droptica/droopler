<?php

declare(strict_types = 1);

namespace Drupal\d_p\Enum;

/**
 * Provides enum for setting types.
 */
enum ParagraphSettingTypes: string implements ParagraphSettingInterface {

  case PARAGRAPH_THEME = 'paragraph_theme';

  case CSS_CLASS = 'custom_class';

  case HEADING_TYPE = 'heading_type';

  case COLUMN_COUNT_DESKTOP = 'column_count_desktop';

  case COLUMN_COUNT_MOBILE = 'column_count_mobile';

  case COLUMN_COUNT_TABLET = 'column_count_tablet';

  case TILES_FEATURED_IMAGES = 'tiles_featured_images';

  case FORM_LAYOUT = 'form_layout';

  case EMBED_LAYOUT = 'embed_layout';

  case IMAGE_SIDE = 'image_side';

  case IMAGE_WIDTH = 'image_width';

  case TILES_SIDE = 'tiles_side';

  case LEFT_SIDE_CONTENT = 'left_side_content';

  case FULL_WIDTH = 'full-width';

  case WITH_PRICE = 'with-price';

  case WITH_PRICE_IN_SIDEBAR = 'with-price-in-sidebar';

  case WITH_GRID = 'with-grid';

  case WITH_TILES = 'with-tiles';

  case THEME_COLORS = 'custom_theme_colors';

  case ADD_DIVIDERS = 'add-dividers';

  case HEADER_INTO_COLUMNS = 'header-into-columns';

  case TEXT_ALIGN = 'text_align';

}
