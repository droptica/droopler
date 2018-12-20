<?php

namespace Drupal\Tests\facets\Functional;

/**
 * Tests the functionality of the facet source config entity.
 *
 * @group facets
 */
class FacetSourceTest extends FacetsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'search_api',
    'facets',
    'facets_search_api_dependency',
    'facets_query_processor',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Make sure we're logged in with a user that has sufficient permissions.
    $this->drupalLogin($this->adminUser);

    // Go to the overview and click the first configure link.
    $this->drupalGet('admin/config/search/facets');
    $this->assertSession()->linkExists('Configure');
    $this->clickLink('Configure');
  }

  /**
   * Tests the facet source editing.
   */
  public function testEditFilterKey() {
    // Change the filter key.
    $edit = [
      'filter_key' => 'fq',
    ];
    $this->assertSession()->fieldExists('filter_key');
    $this->assertSession()->fieldExists('url_processor');
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->addressEquals('admin/config/search/facets');
    $this->assertSession()->pageTextContains('Facet source search_api:views_block__search_api_test_view__block_1 has been saved.');
    $this->clickLink('Configure');

    // Test that saving worked filter_key has the new value.
    $this->assertSession()->fieldExists('filter_key');
    $this->assertSession()->fieldExists('url_processor');
    $this->assertSession()->responseContains('fq');
  }

  /**
   * Tests editing the url processor.
   */
  public function testEditUrlProcessor() {
    // Change the url processor.
    $edit = [
      'url_processor' => 'dummy_query',
    ];
    $this->assertSession()->fieldExists('filter_key');
    $this->assertSession()->fieldExists('url_processor');
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->addressEquals('admin/config/search/facets');
    $this->assertSession()->pageTextContains('Facet source search_api:views_block__search_api_test_view__block_1 has been saved.');
    $this->clickLink('Configure');

    // Test that saving worked and that the url processor has the new value.
    $this->assertSession()->fieldExists('filter_key');
    $this->assertSession()->fieldExists('url_processor');
    /** @var \Behat\Mink\Element\NodeElement[] $elements */
    $elements = $this->xpath('//input[@id=:id]', [':id' => 'edit-url-processor-dummy-query']);
    $this->assertEquals('dummy_query', $elements[0]->getValue());
  }

  /**
   * Tests editing the breadcrumb settings.
   */
  public function testEditBreadcrumbSettings() {
    $this->assertSession()->fieldExists('breadcrumb[active]');
    $this->assertSession()->fieldExists('breadcrumb[group]');
    $this->assertSession()->checkboxNotChecked('breadcrumb[group]');
    $this->assertSession()->checkboxNotChecked('breadcrumb[active]');
    // Change the breadcrumb settings.
    $edit = [
      'breadcrumb[active]' => TRUE,
      'breadcrumb[group]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->addressEquals('admin/config/search/facets');
    $this->assertSession()->pageTextContains('Facet source search_api:views_block__search_api_test_view__block_1 has been saved.');
    $this->clickLink('Configure');

    // Test that saving worked and that the url processor has the new value.
    $this->assertSession()->checkboxChecked('breadcrumb[group]');
    $this->assertSession()->checkboxChecked('breadcrumb[active]');
  }

}
