<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for managing cta field.
 */
interface CtaInterface {

  /**
   * Gets link's url.
   *
   * @return string|null
   *   Link's url or NULL if field is empty or doesn't exist.
   */
  public function getLink(): ?string;

}
