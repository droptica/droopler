<?php

namespace Drupal\d_p\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ParagraphSetting item annotation object.
 *
 * @package Drupal\d_p\Annotation
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
