<?php

namespace Drupal\facets\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Facets Widget annotation.
 *
 * @see \Drupal\facets\Widget\WidgetPluginManager
 * @see plugin_api
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class FacetsWidget extends Plugin {

  /**
   * The widget plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the widget plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The widget description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The possible query types used by this widget.
   *
   * @var array
   */
  public $queryType = [];

  /**
   * Class used to retrieve derivative definitions of the facet_manager.
   *
   * @var string
   */
  public $derivative = '';

}
