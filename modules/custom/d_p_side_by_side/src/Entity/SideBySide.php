<?php

declare(strict_types = 1);

namespace Drupal\d_p_side_by_side\Entity;

use Drupal\d_p\EnableGridInterface;
use Drupal\d_p\EnableGridTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for side by side paragraphs.
 */
class SideBySide extends Paragraph implements EnableGridInterface {

  use EnableGridTrait;

}
