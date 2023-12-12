<?php

declare(strict_types = 1);

namespace Drupal\d_p_counters\Entity;

use Drupal\d_p\ColumnCountInterface;
use Drupal\d_p\ColumnCountTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for group of counters paragraphs.
 */
class GroupOfCounters extends Paragraph implements ColumnCountInterface {

  use ColumnCountTrait;

}
