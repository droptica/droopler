<?php

namespace Drupal\d_geysir\Utility;

/**
 * Provides interface for detecting geysir modal.
 *
 * @package Drupal\d_geysir\Utility
 */
interface ModalDetectorInterface {

  /**
   * Check if the current request is handled by the geysir modal controller.
   *
   * @return bool
   *   True if the request is for geysir modal, false otherwise.
   */
  public function isGeysirModalRequest(): bool;

}
