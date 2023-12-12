<?php

declare(strict_types = 1);

namespace Drupal\d_p_side_tiles\Entity;

use Drupal\d_p\TilesSideInterface;
use Drupal\d_p\TilesSideTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for side tiles paragraphs.
 */
class SideTiles extends Paragraph implements TilesSideInterface {

  use TilesSideTrait;

}
