<?php

declare(strict_types = 1);

namespace Drupal\d_block\Entity;

use Drupal\d_p\FullWidthInterface;
use Drupal\d_p\FullWidthTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Extend the functionality for block paragraph.
 */
class Block extends Paragraph implements FullWidthInterface {

  use FullWidthTrait;

}
