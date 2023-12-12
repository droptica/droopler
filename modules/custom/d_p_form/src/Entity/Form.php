<?php

declare(strict_types = 1);

namespace Drupal\d_p_form\Entity;

use Drupal\d_p\FormLayoutInterface;
use Drupal\d_p\FormLayoutTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for form paragraphs.
 */
class Form extends Paragraph implements FormLayoutInterface {

  use FormLayoutTrait;

}
