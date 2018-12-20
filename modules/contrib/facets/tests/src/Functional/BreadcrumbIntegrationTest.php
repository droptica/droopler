<?php

namespace Drupal\Tests\facets\Functional;

use Drupal\Component\Utility\UrlHelper;

/**
 * Tests the overall functionality of the Facets admin UI.
 *
 * @group facets
 */
class BreadcrumbIntegrationTest extends FacetsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'node',
    'search_api',
    'facets',
    'block',
    'facets_search_api_dependency',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertEquals($this->indexItems($this->indexId), 5, '5 items were indexed.');

    $block = [
      'region' => 'footer',
      'label' => 'Breadcrumbs',
      'provider' => 'system',
    ];
    $this->drupalPlaceBlock('system_breadcrumb_block', $block);
    $this->resetAll();
  }

  /**
   * Tests Breadcrumb integration with grouping.
   */
  public function testGroupingIntegration() {
    $this->editFacetConfig();
    $id = 'keywords';
    $this->createFacet('Keywords', $id, 'keywords');
    $this->resetAll();
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');

    $id = 'type';
    $this->createFacet('Type', $id);
    $this->resetAll();
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');
    $this->drupalPostForm(NULL, ['facet_settings[weight]' => '1'], 'Save');

    // Test with a default filter key.
    $this->editFacetConfig(['filter_key' => 'f']);
    $this->breadcrumbTest();

    // Test with an empty filter key.
    $this->editFacetConfig(['filter_key' => '']);
    $this->breadcrumbTest();

    // Test with a specific filter key.
    $this->editFacetConfig(['filter_key' => 'my_filter_key']);
    $this->breadcrumbTest();
  }

  /**
   * Tests Breadcrumb integration without grouping.
   */
  public function testNonGroupingIntegration() {
    $this->markTestSkipped('Not yet implemented.');
  }

  /**
   * Tests enabling + disabling the breadcrumb label prefix.
   */
  public function testBreadcrumbLabel() {
    $id = 'type';
    $this->createFacet('Type', $id);
    $this->resetAll();
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');
    $this->drupalPostForm(NULL, ['facet_settings[weight]' => '1'], 'Save');
    $this->editFacetConfig(['breadcrumb[before]' => FALSE]);

    $initial_query = ['search_api_fulltext' => 'foo'];
    $this->drupalGet('search-api-test-fulltext', ['query' => $initial_query]);

    $this->clickLink('item');
    $breadcrumb = $this->getSession()->getPage()->find('css', '.breadcrumb');
    $this->assertFalse(strpos($breadcrumb->getText(), 'Type'));
    $breadcrumb->findLink('item');

    $this->editFacetConfig(['breadcrumb[before]' => TRUE]);

    $initial_query = ['search_api_fulltext' => 'foo'];
    $this->drupalGet('search-api-test-fulltext', ['query' => $initial_query]);
    $this->clickLink('item');
    $breadcrumb = $this->getSession()->getPage()->find('css', '.breadcrumb');
    $this->assertTrue(strpos($breadcrumb->getText(), 'Type'));
  }

  /**
   * Edit the facet configuration with the given values.
   *
   * @param array $config
   *   The new configuration for the facet.
   */
  protected function editFacetConfig(array $config = []) {
    $this->drupalGet('admin/config/search/facets');
    $this->clickLink('Configure', 1);
    $default_config = [
      'filter_key' => 'f',
      'url_processor' => 'query_string',
      'breadcrumb[active]' => TRUE,
      'breadcrumb[group]' => TRUE,
    ];
    $edit = array_merge($default_config, $config);
    $this->drupalPostForm(NULL, $edit, 'Save');
  }

  /**
   * Tests Breadcrumb with the given config.
   */
  protected function breadcrumbTest() {
    // Breadcrumb should show Keywords: orange > Type: article, item.
    $initial_query = ['search_api_fulltext' => 'foo', 'test_param' => 1];
    $this->drupalGet('search-api-test-fulltext', ['query' => $initial_query]);

    $this->clickLink('item');
    $this->assertSession()->linkExists('Type: item');

    $this->clickLink('article');
    $this->assertSession()->linkExists('Type: article, item');

    $this->clickLink('orange');
    $this->assertSession()->linkExists('Keywords: orange');
    $this->assertSession()->linkExists('Type: article, item');

    $this->clickLink('Type: article, item');

    $this->assertSession()->linkExists('Keywords: orange');
    $this->assertSession()->linkExists('Type: article, item');
    $this->checkFacetIsActive('orange');
    $this->checkFacetIsActive('item');
    $this->checkFacetIsActive('article');

    $this->clickLink('Keywords: orange');
    $this->assertSession()->linkExists('Keywords: orange');
    $this->assertSession()->linkNotExists('Type: article, item');
    $this->checkFacetIsActive('orange');
    $this->checkFacetIsNotActive('item');
    $this->checkFacetIsNotActive('article');

    // Check that the current url still has the initial parameters.
    $curr_url = UrlHelper::parse($this->getUrl());
    $this->assertArraySubset($initial_query, $curr_url['query']);
  }

}
