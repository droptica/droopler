<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for managing with price setting.
 *
 * @see \Drupal\d_p\WithPriceInterface.
 */
trait WithPriceTrait {

  use BooleanSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function isWithPrice(): bool {
    return $this->getBooleanSettingValue(ParagraphSettingTypes::WITH_PRICE);
  }

}
