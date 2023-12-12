<?php

declare(strict_types = 1);

namespace Drupal\d_p_text_paged\Entity;

use Drupal\d_p\ColumnCountInterface;
use Drupal\d_p\ColumnCountTrait;
use Drupal\d_p\FullWidthInterface;
use Drupal\d_p\FullWidthTrait;
use Drupal\d_p\TextAlignInterface;
use Drupal\d_p\TextAlignTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for text paged paragraphs.
 */
class TextPaged extends Paragraph implements ColumnCountInterface, TextAlignInterface, FullWidthInterface {

  use ColumnCountTrait;
  use TextAlignTrait;
  use FullWidthTrait;

}
