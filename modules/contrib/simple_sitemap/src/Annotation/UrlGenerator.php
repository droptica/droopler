<?php

namespace Drupal\simple_sitemap\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a UrlGenerator item annotation object.
 *
 * @package Drupal\simple_sitemap\Annotation
 *
 * @see \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager
 * @see plugin_api
 *
 * @Annotation
 */
class UrlGenerator extends Plugin {

  /**
   * The generator ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the generator.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * A short description of the generator.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * An integer to determine the weight of this generator relative to others.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * Whether the generator is enabled by default.
   *
   * @var bool
   */
  public $enabled = TRUE;

  /**
   * Default generator settings.
   *
   * @var array
   */
  public $settings = [
    'instantiate_for_each_data_set' => FALSE,
  ];
}
