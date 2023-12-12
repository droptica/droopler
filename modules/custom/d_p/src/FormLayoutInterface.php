<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Interface for managing form layout paragraph setting.
 */
interface FormLayoutInterface {

  /**
   * Sets position of the form.
   *
   * @return string
   *   Location of the form in container.
   */
  public function getFormLayout(): string;

}
