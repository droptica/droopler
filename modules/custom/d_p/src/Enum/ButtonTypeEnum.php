<?php

declare(strict_types = 1);

namespace Drupal\d_p\Enum;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides enum with the options for the button type field.
 */
enum ButtonTypeEnum: string implements LabeledEnumInterface, OptionsEnumInterface {

  use OptionsEnumTrait;

  case Primary = 'primary';
  case PrimaryOutline = 'outline-primary';
  case Secondary = 'secondary';
  case SecondaryOutline = 'outline-secondary';
  case Success = 'success';
  case SuccessOutline = 'outline-success';
  case Danger = 'danger';
  case DangerOutline = 'outline-danger';
  case Warning = 'warning';
  case WarningOutline = 'outline-warning';
  case Info = 'info';
  case InfoOutline = 'outline-info';
  case Light = 'light';
  case LightOutline = 'outline-light';
  case Dark = 'dark';
  case DarkOutline = 'outline-dark';
  case Link = 'Link';

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return match ($this) {
      self::Primary => t('Primary'),
      self::PrimaryOutline => t('Primary outline'),
      self::Secondary => t('Secondary'),
      self::SecondaryOutline => t('Secondary outline'),
      self::Success => t('Success'),
      self::SuccessOutline => t('Success outline'),
      self::Danger => t('Danger'),
      self::DangerOutline => t('Danger outline'),
      self::Warning => t('Warning'),
      self::WarningOutline => t('Warning outline'),
      self::Info => t('Info'),
      self::InfoOutline => t('Info outline'),
      self::Light => t('Light'),
      self::LightOutline => t('Light outline'),
      self::Dark => t('Dark'),
      self::DarkOutline => t('Dark outline'),
      self::Link => t('Link'),
    };
  }

}
