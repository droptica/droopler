<?php

namespace Drupal\facets\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Facets Hierarchy annotation.
 *
 * @see \Drupal\facets\Hierarchy\HierarchyPluginManager
 * @see plugin_api
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class FacetsHierarchy extends Plugin {

  /**
   * The Hierarchy plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the Hierarchy plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The Hierarchy description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
