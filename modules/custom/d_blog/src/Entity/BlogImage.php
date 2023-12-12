<?php

declare(strict_types = 1);

namespace Drupal\d_blog\Entity;

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for product gallery.
 */
class BlogImage extends Paragraph {

  /**
   * Get value of field_d_p_blog_image_full_width.
   *
   * @return bool
   *   Bool field value.
   */
  public function getFullWidthSwitch(): bool {
    return (bool) $this->field_d_p_blog_image_full_width->value;
  }

}
