<?php

declare(strict_types = 1);

namespace Drupal\d_p_carousel\Entity;

use Drupal\d_p\CtaInterface;
use Drupal\d_p\CtaTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for carousel item paragraphs.
 */
class CarouselItem extends Paragraph implements CtaInterface {
  use CtaTrait;

}
