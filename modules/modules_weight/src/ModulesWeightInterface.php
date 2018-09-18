<?php

namespace Drupal\modules_weight;

/**
 * Interface ModulesWeightInterface.
 *
 * @package Drupal\modules_weight
 */
interface ModulesWeightInterface {

  /**
   * Return the modules list ordered by the modules weight.
   *
   * Depending on the force parameter the Core modules will be returned or not.
   *
   * @param bool $show_core_modules
   *   Force to show the core modules.
   *
   * @return array
   *   The modules list.
   */
  public function getModulesList($show_core_modules = FALSE);

}
