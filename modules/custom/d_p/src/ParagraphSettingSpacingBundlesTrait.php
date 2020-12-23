<?php

namespace Drupal\d_p;

/**
 * Provides methods for paragraph settings supporting spacing bundles.
 *
 * @package Drupal\d_p
 */
trait ParagraphSettingSpacingBundlesTrait {

  /**
   * Getter for spacing bundles.
   *
   * @todo We should decide which bundle support spacings
   *    by providing some sort of configuration on paragrpah bundles.
   *
   * @return string[]
   *   Paragraph bundles supporting spacings.
   */
  protected function getSpacingBundles() {
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
