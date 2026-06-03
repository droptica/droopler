<?php

declare(strict_types=1);

namespace Drupal\d_p;

/**
 * Provides interface for select-type paragraph settings.
 */
interface ParagraphSettingSelectInterface {

  /**
   * Getter for the select element available options.
   *
   * @return array<int|string, string|\Stringable>
   *   List of options keyed by option value.
   */
  public function getOptions(): array;

}
