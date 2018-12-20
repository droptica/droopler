<?php

namespace Drupal\search_api_test\Plugin\search_api\datasource;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Datasource\DatasourcePluginBase;
use Drupal\search_api_test\TestPluginTrait;

/**
 * Provides a datasource for testing purposes.
 *
 * @SearchApiDatasource(
 *   id = "search_api_test",
 *   label = @Translation("&quot;Test&quot; datasource"),
 *   description = @Translation("This is the <em>test datasource</em> plugin description."),
 * )
 */
class TestDatasource extends DatasourcePluginBase {

  use TestPluginTrait;

  /**
   * {@inheritdoc}
   */
  public function getItemId(ComplexDataInterface $item) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    return $this->getReturnValue(__FUNCTION__, []);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $remove = $this->getReturnValue(__FUNCTION__, FALSE);
    if ($remove) {
      $this->configuration = [];
    }
    return $remove;
  }

}
