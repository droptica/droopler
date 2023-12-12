<?php

declare(strict_types = 1);

namespace Drupal\d_p\Enum;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides enum with the options for the tiles slide paragraph setting.
 */
enum TilesSideOption: string implements LabeledEnumInterface, OptionsEnumInterface {

  use OptionsEnumTrait;

  case Left = 'left';
  case Right = 'right';

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return match ($this) {
      self::Left => t('Left'),
      self::Right => t('Right'),
    };
  }

}
