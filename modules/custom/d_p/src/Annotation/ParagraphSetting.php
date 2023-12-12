<?php

declare(strict_types = 1);

namespace Drupal\d_p\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ParagraphSetting item annotation object.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class ParagraphSetting extends Plugin {

  /**
   * The human-readable name of the setting.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Default settings.
   *
   * @var array
   */
  public $settings = [];

}
