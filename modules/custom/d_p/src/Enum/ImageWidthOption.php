<?php

declare(strict_types = 1);

namespace Drupal\d_p\Enum;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides enum with the options for the image width paragraph setting.
 */
enum ImageWidthOption: string implements LabeledEnumInterface, OptionsEnumInterface {

  use OptionsEnumTrait;

  case Normal = 'normal';
  case Wide = 'wide';

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return match ($this) {
      self::Normal => t('Normal'),
      self::Wide => t('Wide'),
    };
  }

}
