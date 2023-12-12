<?php

declare(strict_types = 1);

namespace Drupal\d_p_text_blocks\Entity;

use Drupal\d_p\ColumnCountInterface;
use Drupal\d_p\ColumnCountTrait;
use Drupal\d_p\EnableGridInterface;
use Drupal\d_p\EnableGridTrait;
use Drupal\d_p\EnableTilesInterface;
use Drupal\d_p\EnableTilesTrait;
use Drupal\d_p\FullWidthInterface;
use Drupal\d_p\FullWidthTrait;
use Drupal\d_p\HeaderIntoColumnsInterface;
use Drupal\d_p\HeaderIntoColumnsTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for single text block paragraphs.
 */
class GroupOfTextBlock extends Paragraph implements ColumnCountInterface, EnableGridInterface, EnableTilesInterface, FullWidthInterface, HeaderIntoColumnsInterface {

  use ColumnCountTrait;
  use EnableGridTrait;
  use EnableTilesTrait;
  use FullWidthTrait;
  use HeaderIntoColumnsTrait;

}
