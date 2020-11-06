<?php

namespace Drupal\d_p;

/**
 * Provides interface for paragraph setting plugin manager.
 *
 * @package Drupal\d_p
 */
interface ParagraphSettingPluginManagerInterface {

  /**
   * Getter for all plugin instances.
   *
   * @return array
   *   Plugin instances.
   */
  public function getAll(): array;

  /**
   * Getter for settings form built from all plugin instances.
   *
   * @return array
   *   Form elements.
   */
  public function getSettingsForm(): array;

}
