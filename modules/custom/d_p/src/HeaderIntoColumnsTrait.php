<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for managing header into columns paragraph setting.
 *
 * @see \Drupal\d_p\HeaderIntoColumnsInterface.
 */
trait HeaderIntoColumnsTrait {

  use BooleanSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function isHeaderIntoColumns(): bool {
    return $this->getBooleanSettingValue(ParagraphSettingTypes::HEADER_INTO_COLUMNS);
  }

}
