<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\d_p\Enum\ParagraphSettingTypes;

/**
 * Trait for managing column settings.
 *
 * @see \Drupal\d_p\ColumnCountInterface.
 */
trait ColumnCountTrait {

  use ParagraphSettingTrait;

  /**
   * {@inheritdoc}
   */
  public function getMobileBreakpointSize(): int {
    $theme_mobile_breakpoint_value = theme_get_setting('mobile_breakpoint') ?? theme_get_setting('mobile_breakpoint', 'droopler_theme') ?? 540;

    return (int) $theme_mobile_breakpoint_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTabletBreakpointSize(): int {
    $theme_tablet_breakpoint_value = theme_get_setting('tablet_breakpoint') ?? theme_get_setting('tablet_breakpoint', 'droopler_theme') ?? 992;

    return (int) $theme_tablet_breakpoint_value;
  }

  /**
   * Gets column settings.
   *
   * @return array
   *   Array with column settings.
   */
  public function getColumnSettings(): array {
    $settings_field = $this->getParagraphSettingsField();

    if (!$settings_field) {
      return [
        ParagraphSettingTypes::COLUMN_COUNT_MOBILE->value => 1,
        ParagraphSettingTypes::COLUMN_COUNT_TABLET->value => 1,
        ParagraphSettingTypes::COLUMN_COUNT_DESKTOP->value => 1,
      ];
    }

    return [
      ParagraphSettingTypes::COLUMN_COUNT_MOBILE->value => $settings_field->getSettingValue(ParagraphSettingTypes::COLUMN_COUNT_MOBILE),
      ParagraphSettingTypes::COLUMN_COUNT_TABLET->value => $settings_field->getSettingValue(ParagraphSettingTypes::COLUMN_COUNT_TABLET),
      ParagraphSettingTypes::COLUMN_COUNT_DESKTOP->value => $settings_field->getSettingValue(ParagraphSettingTypes::COLUMN_COUNT_DESKTOP),
    ];
  }

}
