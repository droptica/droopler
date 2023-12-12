<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for 'Enable grid' option.
 *
 * @see \Drupal\d_p\EnablePriceInterface.
 */
trait EnablePriceTrait {

  use ParagraphSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function hasEnabledPrice(): bool {
    $settings_field = $this->getParagraphSettingsField();

    return (bool) $settings_field?->getSettingValue(ParagraphSettingTypes::WITH_PRICE, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function hasPriceInSidebar(): bool {
    $settings_field = $this->getParagraphSettingsField();

    return (bool) $settings_field?->getSettingValue(ParagraphSettingTypes::WITH_PRICE_IN_SIDEBAR, FALSE);
  }

}
