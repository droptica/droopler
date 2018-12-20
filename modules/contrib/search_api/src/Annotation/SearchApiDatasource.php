<?php

namespace Drupal\search_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Search API datasource annotation object.
 *
 * @see \Drupal\search_api\Datasource\DatasourcePluginManager
 * @see \Drupal\search_api\Datasource\DatasourceInterface
 * @see \Drupal\search_api\Datasource\DatasourcePluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class SearchApiDatasource extends Plugin {

  /**
   * The datasource plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the datasource plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the datasource.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
