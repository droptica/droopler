<?php

namespace Drupal\Tests\facets\Kernel\Entity;

use Drupal\facets\Entity\FacetSource;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class FacetSourceTest.
 *
 * Tests getters and setters for the FacetSource entity.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets\Entity\FacetSource
 */
class FacetSourceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('facets_facet');
  }

  /**
   * Tests constructor.
   *
   * @covers ::getName
   * @covers ::getFilterKey
   * @covers ::getUrlProcessorName
   */
  public function testConstruct() {
    $fs = new FacetSource(
      [
        'id' => 'llama',
        'name' => 'Llama',
        'filter_key' => 'u',
        'url_processor' => 'monkey',
      ], 'facets_facet_source'
    );

    $this->assertEquals('u', $fs->getFilterKey());
    $this->assertEquals('monkey', $fs->getUrlProcessorName());
    $this->assertEquals('Llama', $fs->getName());
  }

  /**
   * Tests simple getters / setters.
   *
   * @covers ::getName
   * @covers ::setFilterKey
   * @covers ::getFilterKey
   * @covers ::setUrlProcessor
   * @covers ::getUrlProcessorName
   * @covers ::setBreadcrumbSettings
   * @covers ::getBreadcrumbSettings
   */
  public function testGetterSetters() {
    $fs = new FacetSource(['id' => 'llama'], 'facets_facet_source');

    $this->assertNull($fs->getFilterKey());
    $this->assertNull($fs->getName());
    $this->assertEquals('query_string', $fs->getUrlProcessorName());
    $this->assertEmpty($fs->getBreadcrumbSettings());

    $fs->setFilterKey('ab');
    $this->assertEquals('ab', $fs->getFilterKey());

    $fs->setUrlProcessor('test');
    $this->assertEquals('test', $fs->getUrlProcessorName());

    $breadcrumb_settings = ['active' => 1, 'group' => 1];
    $fs->setBreadcrumbSettings($breadcrumb_settings);
    $this->assertEquals($breadcrumb_settings, $fs->getBreadcrumbSettings());
  }

}
