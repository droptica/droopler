<?php

namespace Drupal\Tests\facets_summary\Functional\Rest;

use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * Class FacetSummaryResourceTestBase.
 */
abstract class FacetSummaryResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['facets_summary'];

  /**
   * {@inheritdoc}
   */
  public static $entityTypeId = 'facets_summary';

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
    $entity = FacetsSummary::create();
    $entity->set('id', 'tapir')
      ->set('name', 'Tapir')
      ->set('uuid', 'tapir-uuid')
      ->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [],
      'facet_source_id' => NULL,
      'facets' => [],
      'id' => 'tapir',
      'langcode' => 'en',
      'name' => 'Tapir',
      'processor_configs' => [],
      'status' => TRUE,
      'uuid' => 'tapir-uuid',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update after https://www.drupal.org/node/2300677.
  }

}
