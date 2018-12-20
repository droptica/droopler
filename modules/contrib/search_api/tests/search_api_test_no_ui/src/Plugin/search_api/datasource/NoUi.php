<?php

namespace Drupal\search_api_test_no_ui\Plugin\search_api\datasource;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Datasource\DatasourcePluginBase;

/**
 * Provides a test datasource that should be hidden from the UI.
 *
 * @SearchApiDatasource(
 *   id = "search_api_test_no_ui",
 *   label = @Translation("No UI datasource"),
 *   no_ui = true,
 * )
 */
class NoUi extends DatasourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getItemId(ComplexDataInterface $item) {
    return NULL;
  }

}
