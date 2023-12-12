<?php

declare(strict_types = 1);

namespace Drupal\d_p\Enum;

/**
 * Provides enum for text align setting type.
 */
enum TextAlignOptions: string {

  case Start = 'start';
  case End = 'end';
  case Center = 'center';

}
