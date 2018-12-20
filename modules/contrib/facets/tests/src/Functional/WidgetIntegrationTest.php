<?php

namespace Drupal\Tests\facets\Functional;

/**
 * Tests the overall functionality of the Facets admin UI.
 *
 * @group facets
 */
class WidgetIntegrationTest extends FacetsTestBase {

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
    'facets_query_processor',
    'facets_custom_widget',
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
  }

  /**
   * Tests checkbox widget.
   */
  public function testCheckboxWidget() {
    $id = 't';
    $this->createFacet('Facet & checkbox~', $id);
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');
    $this->drupalPostForm(NULL, ['widget' => 'checkbox'], 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');
  }

  /**
   * Tests links widget's basic functionality.
   */
  public function testLinksWidget() {
    $id = 'links_widget';
    $this->createFacet('>.Facet &* Links', $id);
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');
    $this->drupalPostForm(NULL, ['widget' => 'links'], 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');

    $this->clickLink('item');
    $this->checkFacetIsActive('item');
  }

  /**
   * Tests dropdown widget's basic functionality.
   */
  public function testDropdownWidget() {
    $id = 'select_widget';
    $this->createFacet('Select', $id);
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');
    $this->drupalPostForm(NULL, ['widget' => 'dropdown'], 'Configure widget');
    $this->drupalPostForm(NULL, ['widget' => 'dropdown', 'facet_settings[show_only_one_result]' => TRUE], 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');
  }

  /**
   * Tests the functionality of a widget to hide/show the item-count.
   */
  public function testLinksShowHideCount() {
    $id = 'links_widget';
    $facet_edit_page = 'admin/config/search/facets/' . $id . '/edit';

    $this->createFacet('>.Facet &* Links', $id);

    // Go to the view and check that the facet links are shown with their
    // default settings.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['widget' => 'links', 'widget_config[show_numbers]' => TRUE], 'Save');

    // Go back to the same view and check that links now display the count.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item (3)');
    $this->assertFacetLabel('article (2)');

    $edit = [
      'widget' => 'links',
      'widget_config[show_numbers]' => TRUE,
      'facet_settings[query_operator]' => 'or',
    ];
    $this->drupalPostForm($facet_edit_page, $edit, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item (3)');
    $this->assertFacetLabel('article (2)');
    $this->clickPartialLink('item');
    $this->assertFacetLabel('item (3)');
    $this->assertFacetLabel('article (2)');

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['widget' => 'links', 'widget_config[show_numbers]' => FALSE], 'Save');

    // The count should be hidden again.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');
  }

  /**
   * Tests custom widget.
   *
   * ::requiredFacetProperties in the custom widget requires the
   * hide_non_narrowing_result_processor processor, so check that it's enabled
   * after the custom widget is selected.
   */
  public function testCustomWidget() {
    $id = 'custom_widget';
    $this->createFacet('Custom widget.', $id);

    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');

    $this->assertSession()->checkboxNotChecked('edit-facet-settings-hide-non-narrowing-result-processor-status');
    $this->assertSession()->checkboxNotChecked('edit-facet-settings-show-only-one-result');

    $this->drupalPostForm(NULL, ['widget' => 'custom_widget'], 'Configure widget');
    $this->drupalPostForm(NULL, ['widget' => 'custom_widget'], 'Save');

    $this->assertSession()->checkboxChecked('edit-facet-settings-hide-non-narrowing-result-processor-status');
    $this->assertSession()->checkboxChecked('edit-facet-settings-show-only-one-result');
  }

  /**
   * Tests the facet support for a widget.
   */
  public function testSupportsFacet() {
    $id = 'masked_owl';
    $this->createFacet('Australian masked owl', $id);

    // Go to the facet edit page and check to see if the custom widget shows
    // up.
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');
    $this->assertSession()->pageTextContains('Custom widget');

    // Make the ::supportsFacet method on the custom widget return false.
    \Drupal::state()->set('facets_test_supports_facet', FALSE);

    // Go to the facet edit page and check to see if the custom widget is now
    // hidden.
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');
    $this->assertSession()->pageTextNotContains('Custom widget');
  }

}
