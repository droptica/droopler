<?php

declare(strict_types = 1);

namespace Drupal\d_p_side_embed\Entity;

use Drupal\d_p\ColumnCountInterface;
use Drupal\d_p\ColumnCountTrait;
use Drupal\d_p\EmbedLayoutTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for sideEmbed paragraphs.
 */
class SideEmbed extends Paragraph implements ColumnCountInterface {
  use ColumnCountTrait;
  use EmbedLayoutTrait;

}
