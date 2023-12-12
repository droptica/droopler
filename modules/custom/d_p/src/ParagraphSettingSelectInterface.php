<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Provides interface for select type paragraph settings.
 */
interface ParagraphSettingSelectInterface extends ParagraphSettingInterface {

  /**
   * Getter for select element available options.
   *
   * @return array
   *   List of options.
   */
  public function getOptions(): array;

}
