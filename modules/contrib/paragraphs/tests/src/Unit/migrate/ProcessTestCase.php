<?php

namespace Drupal\Tests\paragraphs\Unit\migrate;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Base class for paragraphs process plugin tests.
 */
abstract class ProcessTestCase extends MigrateProcessTestCase {

  /**
   * The entity bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\PHPUnit_Framework_MockObject_InvocationMocker
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  protected function setup() {
    parent::setUp();

    $this->entityTypeBundleInfo = $this->getMockBuilder(EntityTypeBundleInfo::class)
      ->disableOriginalConstructor()
      ->getMock();
    $bundles = [
      'paragraph_bundle_one' => [],
      'paragraph_bundle_two' => [],
      'field_collection_bundle_one' => [],
      'field_collection_bundle_two' => [],
      'prexisting_bundle_one' => [],
      'prexisting_bundle_two' => [],
    ];
    $this->entityTypeBundleInfo
      ->expects($this->any())
      ->method('getBundleInfo')
      ->with('paragraph')
      ->willReturn($bundles);

  }

}
