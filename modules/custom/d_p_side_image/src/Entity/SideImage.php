<?php

declare(strict_types = 1);

namespace Drupal\d_p_side_image\Entity;

use Drupal\d_p\ImageSettingsTrait;
use Drupal\d_p\ImageSideInterface;
use Drupal\d_p\ImageWidthInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for carousel item paragraphs.
 */
class SideImage extends Paragraph implements ImageWidthInterface, ImageSideInterface {
  use ImageSettingsTrait;

}
