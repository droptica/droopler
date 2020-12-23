<?php

namespace Drupal\d_p;

/**
 * Provides interface for select type paragraph settings.
 *
 * @package Drupal\d_p
 */
interface ParagraphSettingSelectInterface {

  /**
   * Getter for select element available options.
   *
   * @return array
   *   List of options.
   */
  public function getOptions(): array;

}
