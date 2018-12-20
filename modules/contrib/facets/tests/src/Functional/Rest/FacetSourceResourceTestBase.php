<?php

namespace Drupal\Tests\facets\Functional\Rest;

use Drupal\facets\Entity\FacetSource;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * Class FacetSourceResourceTestBase.
 */
abstract class FacetSourceResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['facets'];

  /**
   * {@inheritdoc}
   */
  public static $entityTypeId = 'facets_facet_source';

  /**
   * {@inheritdoc}
   */
  protected static $labelFieldName = 'name';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer facets']);
  }

  /**
   * {@inheritdoc}
   */
  public function createEntity() {
    $entity = FacetSource::create();
    $entity->set('id', 'red_panda')
      ->set('name', 'Red panda')
      ->set('uuid', 'red-panda-uuid')
      ->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'breadcrumb' => [],
      'dependencies' => [],
      'filter_key' => NULL,
      'id' => 'red_panda',
      'langcode' => 'en',
      'name' => 'Red panda',
      'uuid' => 'red-panda-uuid',
      'status' => TRUE,
      'url_processor' => 'query_string',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update after https://www.drupal.org/node/2300677.
  }

}
