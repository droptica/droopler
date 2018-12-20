<?php

namespace Drupal\Tests\facets\Functional\Rest;

use Drupal\facets\Entity\Facet;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * Class FacetResourceTestBase.
 */
abstract class FacetResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['facets'];

  /**
   * {@inheritdoc}
   */
  public static $entityTypeId = 'facets_facet';

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
    $facet = Facet::create();
    $facet->set('id', 'owl');
    $facet->set('uuid', 'uuid-owl');
    $facet->setWidget('links', ['show_numbers' => TRUE]);
    $facet->addProcessor([
      'processor_id' => 'url_processor_handler',
      'weights' => ['pre_query' => -10, 'build' => -10],
      'settings' => [],
    ]);
    $facet->setEmptyBehavior(['behavior' => 'none']);
    $facet->save();

    return $facet;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [],
      'empty_behavior' => ['behavior' => 'none'],
      'enable_parent_when_child_gets_disabled' => TRUE,
      'exclude' => NULL,
      'expand_hierarchy' => FALSE,
      'facet_source_id' => NULL,
      'field_identifier' => NULL,
      'hard_limit' => NULL,
      'id' => 'owl',
      'langcode' => 'en',
      'min_count' => 1,
      'name' => NULL,
      'only_visible_when_facet_source_is_visible' => NULL,
      'processor_configs' => [
        'url_processor_handler' => [
          'processor_id' => 'url_processor_handler',
          'settings' => [],
          'weights' => [
            'build' => -10,
            'pre_query' => -10,
          ],
        ],
      ],
      'query_operator' => NULL,
      'show_only_one_result' => FALSE,
      'show_title' => NULL,
      'status' => TRUE,
      'url_alias' => NULL,
      'use_hierarchy' => FALSE,
      'uuid' => 'uuid-owl',
      'weight' => NULL,
      'widget' => [
        'config' => [
          'show_numbers' => TRUE,
        ],
        'type' => 'links',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update after https://www.drupal.org/node/2300677.
  }

}
