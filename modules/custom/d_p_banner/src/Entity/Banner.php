<?php

declare(strict_types = 1);

namespace Drupal\d_p_banner\Entity;

use Drupal\d_p\LeftSideContentInterface;
use Drupal\d_p\LeftSideContentTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for banner paragraphs.
 */
class Banner extends Paragraph implements LeftSideContentInterface {

  use LeftSideContentTrait;

}
