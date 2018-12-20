<?php

namespace Drupal\Tests\facets\Functional;

use Drupal\Core\Url;
use Drupal\facets\Plugin\facets\query_type\SearchApiDate;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Tests the overall functionality of the Facets admin UI.
 *
 * @group facets
 */
class IntegrationTest extends FacetsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['views_ui'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertEquals(5, $this->indexItems($this->indexId), '5 items were indexed.');

    // Make absolutely sure the ::$blocks variable doesn't pass information
    // along between tests.
    $this->blocks = NULL;
  }

  /**
   * Tests permissions.
   */
  public function testOverviewPermissions() {
    $facet_overview = '/admin/config/search/facets';

    // Login with a user that is not authorized to administer facets and test
    // that we're correctly getting a 403 HTTP response code.
    $this->drupalLogin($this->unauthorizedUser);
    $this->drupalGet($facet_overview);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains('You are not authorized to access this page');

    // Login with a user that has the correct permissions and test for the
    // correct HTTP response code.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($facet_overview);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests facets admin pages availability.
   */
  public function testAdminPages() {
    $pages = [
      '/admin/config/search/facets',
      '/admin/config/search/facets/add-facet',
      '/admin/config/search/facets/facet-sources/views_page/edit',
    ];

    foreach ($pages as $page) {
      $this->drupalGet($page);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  /**
   * Tests various operations via the Facets' admin UI.
   */
  public function testFramework() {
    $facet_name = "Test Facet name";
    $facet_id = 'test_facet_name';

    // Check if the overview is empty.
    $this->checkEmptyOverview();

    // Add a new facet and edit it. Check adding a duplicate.
    $this->addFacet($facet_name);
    $this->editFacet($facet_name);
    $this->addFacetDuplicate($facet_name);

    // By default, the view should show all entities.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');

    // Create and place a block for "Test Facet name" facet.
    $this->blocks[$facet_id] = $this->createBlock($facet_id);

    // Verify that the facet results are correct.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('item');
    $this->assertSession()->pageTextContains('article');

    // Verify that facet blocks appear as expected.
    $this->assertFacetBlocksAppear();

    // Verify that the facet only shows when the facet source is visible, it
    // should not show up on the user page.
    $this->setOptionShowOnlyWhenFacetSourceVisible($facet_name);
    $this->drupalGet('user/2');
    $this->assertNoFacetBlocksAppear();

    // Do not show the block on empty behaviors.
    $this->clearIndex();
    $this->drupalGet('search-api-test-fulltext');

    // Verify that no facet blocks appear. Empty behavior "None" is selected by
    // default.
    $this->assertNoFacetBlocksAppear();

    // Verify that the "empty_text" appears as expected.
    $this->setEmptyBehaviorFacetText($facet_name);
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->responseContains('block-test-facet-name');
    $this->assertSession()->responseContains('No results found for this block!');

    // Delete the block.
    $this->deleteBlock($facet_id);

    // Delete the facet and make sure the overview is empty again.
    $this->deleteUnusedFacet($facet_name);
    $this->checkEmptyOverview();
  }

  /**
   * Tests that a block view also works.
   */
  public function testBlockView() {
    $facet_id = 'block_view_facet';

    $webAssert = $this->assertSession();
    $this->addFacet('Block view facet', 'type', 'search_api:views_block__search_api_test_view__block_1');
    $this->createBlock($facet_id);
    $this->drupalGet('admin/config/search/facets/' . $facet_id . '/edit');
    $webAssert->checkboxNotChecked('facet_settings[only_visible_when_facet_source_is_visible]');

    // Place the views block in the footer of all pages.
    $block_settings = [
      'region' => 'sidebar_first',
      'id' => 'view_block',
    ];
    $this->drupalPlaceBlock('views_block:search_api_test_view-block_1', $block_settings);

    // By default, the view should show all entities.
    $this->drupalGet('<front>');
    $webAssert->pageTextContains('Fulltext test index');
    $webAssert->pageTextContains('Displaying 5 search results');
    $webAssert->pageTextContains('item');
    $webAssert->pageTextContains('article');

    // Click the item link, and test that filtering of results actually works.
    $this->clickLink('item');
    $webAssert->pageTextContains('Displaying 3 search results');
  }

  /**
   * Tests for deleting a block.
   */
  public function testBlockDelete() {
    $name = 'Tawny-browed owl';
    $id = 'tawny_browed_owl';

    // Add a new facet.
    $this->createFacet($name, $id);

    $block = $this->blocks[$id];
    $block_id = $block->label();

    $this->drupalGet('admin/structure/block');
    $this->assertSession()->pageTextContains($block_id);

    $this->drupalGet('admin/structure/block/library/classy');
    $this->assertSession()->pageTextContains($name);

    $this->drupalGet('admin/config/search/facets/' . $id . '/delete');
    $this->assertSession()->pageTextContains('The listed configuration will be deleted.');
    $this->assertSession()->pageTextContains($block->label());
    $this->drupalPostForm(NULL, [], 'Delete');

    $this->drupalGet('admin/structure/block/library/classy');
    $this->assertSession()->pageTextNotContains($name);
  }

  /**
   * Tests that an url alias works correctly.
   */
  public function testUrlAlias() {
    $facet_id = 'ab_facet';
    $facet_edit_page = '/admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet('ab Facet', $facet_id);

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');

    $this->clickLink('item');
    $url = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['f[0]' => 'ab_facet:item']]);
    $this->assertSession()->addressEquals($url);

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['facet_settings[url_alias]' => 'llama'], 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');

    $this->clickLink('item');
    $url = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['f[0]' => 'llama:item']]);
    $this->assertSession()->addressEquals($url);
  }

  /**
   * Tests facet dependencies.
   */
  public function testFacetDependencies() {
    $facet_name = "DependableFacet";
    $facet_id = 'dependablefacet';

    $depending_facet_name = "DependingFacet";
    $depending_facet_id = "dependingfacet";

    $this->addFacet($facet_name);
    $this->addFacet($depending_facet_name, 'keywords');

    // Create both facets as blocks and add them on the page.
    $this->blocks[$facet_id] = $this->createBlock($facet_id);
    $this->blocks[$depending_facet_id] = $this->createBlock($depending_facet_id);

    // Go to the view and test that both facets are shown. Item and article
    // come from the DependableFacet, orange and grape come from DependingFacet.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('grape');
    $this->assertFacetLabel('orange');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');
    $this->assertFacetBlocksAppear();

    // Change the visiblity settings of the DependingFacet.
    $this->drupalGet('admin/config/search/facets/' . $depending_facet_id . '/edit');
    $edit = [
      'facet_settings[dependent_processor][status]' => TRUE,
      'facet_settings[dependent_processor][settings][' . $facet_id . '][enable]' => TRUE,
      'facet_settings[dependent_processor][settings][' . $facet_id . '][condition]' => 'values',
      'facet_settings[dependent_processor][settings][' . $facet_id . '][values]' => 'item',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Go to the view and test that only the types are shown.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->linkNotExists('grape');
    $this->assertSession()->linkNotExists('orange');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');

    // Click on the item, and test that this shows the keywords.
    $this->clickLink('item');
    $this->assertFacetLabel('grape');
    $this->assertFacetLabel('orange');

    // Go back to the view, click on article and test that the keywords are
    // hidden.
    $this->drupalGet('search-api-test-fulltext');
    $this->clickLink('article');
    $this->assertSession()->linkNotExists('grape');
    $this->assertSession()->linkNotExists('orange');

    // Change the visibility settings to negate the previous settings.
    $this->drupalGet('admin/config/search/facets/' . $depending_facet_id . '/edit');
    $edit = [
      'facet_settings[dependent_processor][status]' => TRUE,
      'facet_settings[dependent_processor][settings][' . $facet_id . '][enable]' => TRUE,
      'facet_settings[dependent_processor][settings][' . $facet_id . '][condition]' => 'values',
      'facet_settings[dependent_processor][settings][' . $facet_id . '][values]' => 'item',
      'facet_settings[dependent_processor][settings][' . $facet_id . '][negate]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Go to the view and test only the type facet is shown.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');
    $this->assertFacetLabel('grape');
    $this->assertFacetLabel('orange');

    // Click on the article, and test that this shows the keywords.
    $this->clickLink('article');
    $this->assertFacetLabel('grape');
    $this->assertFacetLabel('orange');

    // Go back to the view, click on item and test that the keywords are
    // hidden.
    $this->drupalGet('search-api-test-fulltext');
    $this->clickLink('item');
    $this->assertSession()->linkNotExists('grape');
    $this->assertSession()->linkNotExists('orange');
  }

  /**
   * Tests the facet's and/or functionality.
   */
  public function testAndOrFacet() {
    $facet_name = 'test & facet';
    $facet_id = 'test_facet';
    $facet_edit_page = 'admin/config/search/facets/' . $facet_id . '/edit';

    $this->createFacet($facet_name, $facet_id);

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['facet_settings[query_operator]' => 'and'], 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');

    $this->clickLink('item');
    $this->checkFacetIsActive('item');
    $this->assertSession()->linkNotExists('article');

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['facet_settings[query_operator]' => 'or'], 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('item');
    $this->assertFacetLabel('article');

    $this->clickLink('item');
    $this->checkFacetIsActive('item');
    $this->assertFacetLabel('article');

    // Verify the number of results for OR functionality.
    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['widget' => 'links', 'widget_config[show_numbers]' => TRUE], 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $this->clickLink('item (3)');
    $this->assertFacetLabel('article (2)');

  }

  /**
   * Tests that we disallow unwanted values when creating a facet trough the UI.
   */
  public function testUnwantedValues() {
    // Go to the Add facet page and make sure that returns a 200.
    $facet_add_page = '/admin/config/search/facets/add-facet';
    $this->drupalGet($facet_add_page);
    $this->assertSession()->statusCodeEquals(200);

    // Configure the facet source by selecting one of the Search API views.
    $this->drupalGet($facet_add_page);
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api:views_page__search_api_test_view__page_1'], 'Configure facet source');

    // Fill in all fields and make sure the 'field is required' message is no
    // longer shown.
    $facet_source_form = [
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
      'facet_source_configs[search_api:views_page__search_api_test_view__page_1][field_identifier]' => 'type',
    ];
    $this->drupalPostForm(NULL, $facet_source_form, 'Save');

    $form_values = [
      'name' => 'name 1',
      'id' => 'name 1',
    ];
    $this->drupalPostForm(NULL, $form_values, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    $form_values = [
      'name' => 'name 1',
      'id' => 'name:&1',
    ];
    $this->drupalPostForm(NULL, $form_values, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    // Post the form with valid values, so we can test the next step.
    $form_values = [
      'name' => 'name 1',
      'id' => 'name_1',
    ];
    $this->drupalPostForm(NULL, $form_values, 'Save');

    // Create an array of values that are not allowed in the url.
    $unwanted_values = [' ', '!', '@', '#', '$', '%', '^', '&'];
    foreach ($unwanted_values as $unwanted_value) {
      $form_values = [
        'facet_settings[url_alias]' => 'alias' . $unwanted_value . '1',
      ];
      $this->drupalPostForm(NULL, $form_values, 'Save');
      $this->assertSession()->pageTextContains('The URL alias contains characters that are not allowed.');
    }

    // Post an alias with allowed values.
    $form_values = [
      'facet_settings[url_alias]' => 'alias~-_.1',
    ];
    $this->drupalPostForm(NULL, $form_values, 'Save');
    $this->assertSession()->pageTextContains('Facet name 1 has been updated.');
  }

  /**
   * Tests the facet's exclude functionality.
   */
  public function testExcludeFacet() {
    $facet_name = 'test & facet';
    $facet_id = 'test_facet';
    $facet_edit_page = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet($facet_name, $facet_id);

    $this->drupalGet($facet_edit_page);
    $this->assertSession()->checkboxNotChecked('edit-facet-settings-exclude');
    $this->drupalPostForm(NULL, ['facet_settings[exclude]' => TRUE], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-facet-settings-exclude');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('foo bar baz');
    $this->assertSession()->pageTextContains('foo baz');
    $this->assertFacetLabel('item');

    $this->clickLink('item');
    $this->checkFacetIsActive('item');
    $this->assertSession()->pageTextContains('foo baz');
    $this->assertSession()->pageTextContains('bar baz');
    $this->assertSession()->pageTextNotContains('foo bar baz');

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['facet_settings[exclude]' => FALSE], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('edit-facet-settings-exclude');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('foo bar baz');
    $this->assertSession()->pageTextContains('foo baz');
    $this->assertFacetLabel('item');

    $this->clickLink('item');
    $this->checkFacetIsActive('item');
    $this->assertSession()->pageTextContains('foo bar baz');
    $this->assertSession()->pageTextContains('foo test');
    $this->assertSession()->pageTextContains('bar');
    $this->assertSession()->pageTextNotContains('foo baz');
  }

  /**
   * Tests the facet's exclude functionality for a date field.
   */
  public function testExcludeFacetDate() {
    $field_name = 'created';
    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');
    $entity_test_storage->create([
      'name' => 'foo new',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange'],
      'category' => 'item_category',
      $field_name => 1490000000,
    ])->save();

    $entity_test_storage->create([
      'name' => 'foo old',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange'],
      'category' => 'item_category',
      $field_name => 1460000000,
    ])->save();

    $this->indexItems($this->indexId);

    $facet_id = "created";

    // Create facet.
    $facet_edit_page = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet("Created", $facet_id, $field_name);

    $form = [
      'widget' => 'links',
      'facet_settings[exclude]' => 0,
      'facet_settings[date_item][status]' => 1,
      'facet_settings[date_item][settings][date_display]' => 'actual_date',
      'facet_settings[date_item][settings][granularity]' => SearchApiDate::FACETAPI_DATE_MONTH,
    ];
    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('foo old');
    $this->assertSession()->pageTextContains('foo new');
    $this->clickLink('March 2017');
    $this->checkFacetIsActive('March 2017');
    $this->assertSession()->pageTextContains('foo new');
    $this->assertSession()->pageTextNotContains('foo old');

    $this->drupalGet($facet_edit_page);
    $this->assertSession()->checkboxNotChecked('edit-facet-settings-exclude');
    $this->drupalPostForm(NULL, ['facet_settings[exclude]' => 1], 'Save');
    $this->assertSession()->checkboxChecked('edit-facet-settings-exclude');

    $this->drupalGet('search-api-test-fulltext');
    $this->clickLink('March 2017');
    $this->checkFacetIsActive('March 2017');
    $this->assertSession()->pageTextContains('foo old');
    $this->assertSession()->pageTextNotContains('foo new');
  }

  /**
   * Tests allow only one active item.
   */
  public function testAllowOneActiveItem() {
    $facet_name = 'Spotted wood owl';
    $facet_id = 'spotted_wood_owl';
    $facet_edit_page = 'admin/config/search/facets/' . $facet_id;

    $this->createFacet($facet_name, $facet_id, 'keywords');

    $this->drupalGet($facet_edit_page . '/edit');
    $edit = ['facet_settings[show_only_one_result]' => TRUE];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertFacetLabel('grape');
    $this->assertFacetLabel('orange');

    $this->clickLink('grape');
    $this->assertSession()->pageTextContains('Displaying 3 search results');
    $this->checkFacetIsActive('grape');
    $this->assertFacetLabel('orange');

    $this->clickLink('orange');
    $this->assertSession()->pageTextContains('Displaying 3 search results');
    $this->assertFacetLabel('grape');
    $this->checkFacetIsActive('orange');
  }

  /**
   * Tests calculations of facet count.
   */
  public function testFacetCountCalculations() {
    $this->addFacet('Type');
    $this->addFacet('Keywords', 'keywords');
    $this->createBlock('type');
    $this->createBlock('keywords');

    $edit = [
      'widget' => 'links',
      'widget_config[show_numbers]' => '1',
      'facet_settings[query_operator]' => 'and',
    ];
    $this->drupalPostForm('admin/config/search/facets/keywords/edit', $edit, 'Save');
    $this->drupalPostForm('admin/config/search/facets/type/edit', $edit, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertFacetLabel('article (2)');
    $this->assertFacetLabel('grape (3)');

    // Make sure that after clicking on article, which has only 2 entities,
    // there are only 2 items left in the results for other facets as well.
    // In this case, that means we can't have 3 entities tagged with grape. Both
    // remaining entities are tagged with grape and strawberry.
    $this->clickPartialLink('article');
    $this->assertSession()->pageTextNotContains('(3)');
    $this->assertFacetLabel('grape (2)');
    $this->assertFacetLabel('strawberry (2)');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertFacetLabel('article (2)');
    $this->assertFacetLabel('grape (3)');

    // Make sure that after clicking on grape, which has only 3 entities, there
    // are only 3 items left in the results for other facets as well. In this
    // case, that means 2 entities of type article and 1 item.
    $this->clickPartialLink('grape');
    $this->assertSession()->pageTextContains('Displaying 3 search results');
    $this->assertFacetLabel('article (2)');
    $this->assertFacetLabel('item (1)');
  }

  /**
   * Tests what happens when a dependency is removed.
   */
  public function testOnViewRemoval() {
    $id = "owl";
    $name = "Owl";
    $this->createFacet($name, $id);

    $this->drupalGet('/admin/config/search/facets');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the expected facet sources and the owl facet are shown.
    $this->assertSession()->pageTextContains('search_api:views_block__search_api_test_view__block_1');
    $this->assertSession()->pageTextContains('search_api:views_page__search_api_test_view__page_1');
    $this->assertSession()->pageTextContains($name);

    // Delete the view on which both facet sources are based.
    $view = View::load('search_api_test_view');
    $view->delete();

    // Go back to the overview, make sure that the page doesn't show any errors
    // and the facet/facet source are deleted.
    $this->drupalGet('/admin/config/search/facets');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('search_api:views_page__search_api_test_view__page_1');
    $this->assertSession()->pageTextNotContains('search_api:views_block__search_api_test_view__block_1');
    $this->assertSession()->pageTextNotContains($name);
  }

  /**
   * Tests what happens when a dependency is removed.
   */
  public function testOnViewDisplayRemoval() {
    $admin_user = $this->drupalCreateUser([
      'administer search_api',
      'administer facets',
      'access administration pages',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer blocks',
      'administer views',
    ]);
    $this->drupalLogin($admin_user);

    $id = "owl";
    $name = "Owl";
    $this->createFacet($name, $id);

    $this->drupalGet('/admin/config/search/facets');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the expected facet sources and the owl facet are shown.
    $this->assertSession()->pageTextContains('search_api:views_block__search_api_test_view__block_1');
    $this->assertSession()->pageTextContains('search_api:views_page__search_api_test_view__page_1');
    $this->assertSession()->pageTextContains($name);

    // Delete the view display for the page.
    $this->drupalGet('admin/structure/views/view/search_api_test_view');
    $this->drupalPostForm(NULL, [], 'Delete Page');
    $this->drupalPostForm(NULL, [], 'Save');

    // Go back to the overview, make sure that the page doesn't show any errors
    // and the facet/facet source are deleted.
    $this->drupalGet('/admin/config/search/facets');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('search_api:views_page__search_api_test_view__page_1');
    $this->assertSession()->pageTextContains('search_api:views_block__search_api_test_view__block_1');
    $this->assertSession()->pageTextNotContains($name);
  }

  /**
   * Tests the hard limit setting.
   */
  public function testHardLimit() {
    $this->createFacet('Owl', 'owl', 'keywords');

    $edit = [
      'widget' => 'links',
      'widget_config[show_numbers]' => '1',
      'facet_sorting[display_value_widget_order][status]' => TRUE,
      'facet_sorting[active_widget_order][status]' => FALSE,
    ];
    $this->drupalPostForm('admin/config/search/facets/owl/edit', $edit, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertFacetLabel('grape (3)');
    $this->assertFacetLabel('orange (3)');
    $this->assertFacetLabel('apple (2)');
    $this->assertFacetLabel('banana (1)');
    $this->assertFacetLabel('strawberry (2)');

    $edit['facet_settings[hard_limit]'] = 3;
    $this->drupalPostForm('admin/config/search/facets/owl/edit', $edit, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    // We're still testing for 5 search results here, the hard limit only limits
    // the facets, not the search results.
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertFacetLabel('grape (3)');
    $this->assertFacetLabel('orange (3)');
    $this->assertFacetLabel('apple (2)');
    $this->assertSession()->pageTextNotContains('banana (0)');
    $this->assertSession()->pageTextNotContains('strawberry (0)');
  }

  /**
   * Test minimum amount of items.
   */
  public function testMinimumAmount() {
    $id = "elf_owl";
    $name = "Elf owl";
    $this->createFacet($name, $id);

    // Show the amount of items.
    $edit = [
      'widget' => 'links',
      'widget_config[show_numbers]' => '1',
      'facet_settings[min_count]' => 1,
    ];
    $this->drupalPostForm('admin/config/search/facets/elf_owl/edit', $edit, 'Save');

    // See that both article and item are showing.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertFacetLabel('article (2)');
    $this->assertFacetLabel('item (3)');

    // Make sure that a facet needs at least 3 results.
    $edit = [
      'widget' => 'links',
      'widget_config[show_numbers]' => '1',
      'facet_settings[min_count]' => 3,
    ];
    $this->drupalPostForm('admin/config/search/facets/elf_owl/edit', $edit, 'Save');

    // See that article is now hidden, item should still be showing.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertSession()->pageTextNotContains('article');
    $this->assertFacetLabel('item (3)');
  }

  /**
   * Tests the visibility of facet source.
   */
  public function testFacetSourceVisibility() {
    $this->createFacet('Vicuña', 'vicuna');
    $edit = [
      'facet_settings[only_visible_when_facet_source_is_visible]' => FALSE,
    ];
    $this->drupalPostForm('/admin/config/search/facets/vicuna/edit', $edit, 'Save');

    // Test that the facet source is visible on the search page and user/2 page.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetBlocksAppear();
    $this->drupalGet('user/2');
    $this->assertFacetBlocksAppear();

    // Change the facet to only show when it's source is visible.
    $edit = [
      'facet_settings[only_visible_when_facet_source_is_visible]' => TRUE,
    ];
    $this->drupalPostForm('/admin/config/search/facets/vicuna/edit', $edit, 'Save');

    // Test that the facet still apears on the search page but is hidden on the
    // user page.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetBlocksAppear();
    $this->drupalGet('user/2');
    $this->assertNoFacetBlocksAppear();
  }

  /**
   * Tests behavior with multiple enabled facets and their interaction.
   */
  public function testMultipleFacets() {
    // Create 2 facets.
    $this->createFacet('Snow Owl', 'snow_owl');
    // Clear all the caches between building the 2 facets - because things fail
    // otherwise.
    $this->resetAll();
    $this->createFacet('Forest Owl', 'forest_owl', 'category');

    // Make sure numbers are displayed.
    $edit = [
      'widget_config[show_numbers]' => 1,
      'facet_settings[min_count]' => 0,
    ];
    $this->drupalPostForm('admin/config/search/facets/snow_owl/edit', $edit, 'Save');
    $this->drupalPostForm('admin/config/search/facets/forest_owl/edit', $edit, 'Save');

    // Go to the view and check the default behavior.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertFacetLabel('item (3)');
    $this->assertFacetLabel('article (2)');
    $this->assertFacetLabel('item_category (2)');
    $this->assertFacetLabel('article_category (2)');

    // Start filtering.
    $this->clickPartialLink('item_category');
    $this->assertSession()->pageTextContains('Displaying 2 search results');
    $this->checkFacetIsActive('item_category');
    $this->assertFacetLabel('item (2)');

    // Go back to the overview and start another filter, from the second facet
    // block this time.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->clickPartialLink('article (2)');
    $this->assertSession()->pageTextContains('Displaying 2 search results');
    $this->checkFacetIsActive('article');
    $this->assertFacetLabel('article_category (2)');
    $this->assertFacetLabel('item_category (0)');
  }

  /**
   * Tests cloning of a facet.
   */
  public function testClone() {
    $id = "western_screech_owl";
    $name = "Western screech owl";
    $this->createFacet($name, $id);

    $this->drupalGet('admin/config/search/facets');
    $this->assertSession()->pageTextContains('Western screech owl');
    $this->assertSession()->linkExists('Clone facet');
    $this->clickLink('Clone facet');

    $clone_edit = [
      'destination_facet_source' => 'search_api:views_block__search_api_test_view__block_1',
      'name' => 'Eastern screech owl',
      'id' => 'eastern_screech_owl',
    ];
    $this->submitForm($clone_edit, 'Duplicate');
    $this->assertSession()->pageTextContains('Facet cloned to Eastern screech owl');

    $this->drupalGet('admin/config/search/facets');
    $this->assertSession()->pageTextContains('Western screech owl');
    $this->assertSession()->pageTextContains('Eastern screech owl');
  }

  /**
   * Check that the disabling of the cache works.
   */
  public function testViewsCacheDisable() {
    // Load the view, verify cache settings.
    $view = Views::getView('search_api_test_view');
    $view->setDisplay('page_1');
    $current_cache = $view->display_handler->getOption('cache');
    $this->assertEquals('none', $current_cache['type']);
    $view->display_handler->setOption('cache', ['type' => 'tag']);
    $view->save();
    $current_cache = $view->display_handler->getOption('cache');
    $this->assertEquals('tag', $current_cache['type']);

    // Create a facet and check for the cache disabled message.
    $id = "western_screech_owl";
    $name = "Western screech owl";
    $this->createFacet($name, $id);
    $this->drupalPostForm('admin/config/search/facets/' . $id . '/settings', [], 'Save');
    $this->assertSession()->pageTextContains('Caching of view Search API Test Fulltext search view has been disabled.');

    // Check the view's cache settings again to see if they've been updated.
    $view = Views::getView('search_api_test_view');
    $view->setDisplay('page_1');
    $current_cache = $view->display_handler->getOption('cache');
    $this->assertEquals('none', $current_cache['type']);
  }

  /**
   * Tests that the configuration for showing a title works.
   */
  public function testShowTitle() {
    $this->createFacet("Llama", 'llama');
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextNotContains('Llama');
    $this->drupalGet('admin/config/search/facets/llama/edit');
    $this->drupalPostForm(NULL, ['facet_settings[show_title]' => TRUE], 'Save');
    $this->assertSession()->checkboxChecked('Show title of facet');
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->responseContains('<h3>Llama</h3>');
    $this->assertSession()->pageTextContains('Llama');
  }

  /**
   * Configures empty behavior option to show a text on empty results.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function setEmptyBehaviorFacetText($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_display_page = '/admin/config/search/facets/' . $facet_id . '/edit';

    // Go to the facet edit page and make sure "edit facet %facet" is present.
    $this->drupalGet($facet_display_page);
    $this->assertSession()->statusCodeEquals(200);

    // Configure the text for empty results behavior.
    $edit = [
      'facet_settings[empty_behavior]' => 'text',
      'facet_settings[empty_behavior_container][empty_behavior_text][value]' => 'No results found for this block!',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

  }

  /**
   * Configures a facet to only be visible when accessing to the facet source.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function setOptionShowOnlyWhenFacetSourceVisible($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_edit_page = '/admin/config/search/facets/' . $facet_id . '/edit';
    $this->drupalGet($facet_edit_page);
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'facet_settings[only_visible_when_facet_source_is_visible]' => TRUE,
      'widget' => 'links',
      'widget_config[show_numbers]' => '0',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
  }

  /**
   * Tests that the facet overview is empty.
   */
  protected function checkEmptyOverview() {
    $facet_overview = '/admin/config/search/facets';
    $this->drupalGet($facet_overview);
    $this->assertSession()->statusCodeEquals(200);

    // The list overview has Field: field_name as description. This tests on the
    // absence of that.
    $this->assertSession()->pageTextNotContains('Field:');

    // Check that the expected facet sources are shown.
    $this->assertSession()->pageTextContains('search_api:views_block__search_api_test_view__block_1');
    $this->assertSession()->pageTextContains('search_api:views_page__search_api_test_view__page_1');
  }

  /**
   * Tests adding a facet trough the interface.
   *
   * @param string $facet_name
   *   The name of the facet.
   * @param string $facet_type
   *   The field of the facet.
   * @param string $source_id
   *   The facet source id.
   */
  protected function addFacet($facet_name, $facet_type = 'type', $source_id = 'search_api:views_page__search_api_test_view__page_1') {
    $facet_id = $this->convertNameToMachineName($facet_name);

    // Go to the Add facet page and make sure that returns a 200.
    $facet_add_page = '/admin/config/search/facets/add-facet';
    $this->drupalGet($facet_add_page);
    $this->assertSession()->statusCodeEquals(200);

    $form_values = [
      'name' => '',
      'id' => $facet_id,
    ];

    // Try filling out the form, but without having filled in a name for the
    // facet to test for form errors.
    $this->drupalPostForm($facet_add_page, $form_values, 'Save');
    $this->assertSession()->pageTextContains('Name field is required.');
    $this->assertSession()->pageTextContains('Facet source field is required.');

    // Make sure that when filling out the name, the form error disappears.
    $form_values['name'] = $facet_name;
    $this->drupalPostForm(NULL, $form_values, 'Save');
    $this->assertSession()->pageTextNotContains('Name field is required.');

    // Configure the facet source by selecting one of the Search API views.
    $this->drupalGet($facet_add_page);
    $this->drupalPostForm(NULL, ['facet_source_id' => '' . $source_id . ''], 'Configure facet source');

    // The field is still required.
    $this->drupalPostForm(NULL, $form_values, 'Save');
    $this->assertSession()->pageTextContains('Field field is required.');

    // Fill in all fields and make sure the 'field is required' message is no
    // longer shown.
    $facet_source_form = [
      'facet_source_id' => $source_id,
      'facet_source_configs[' . $source_id . '][field_identifier]' => $facet_type,
    ];
    $this->drupalPostForm(NULL, $form_values + $facet_source_form, 'Save');
    $this->assertSession()->pageTextNotContains('field is required.');

    // Make sure that the redirection to the display page is correct.
    $this->assertSession()->pageTextContains('Facet ' . $facet_name . ' has been created.');
    $this->assertSession()->addressEquals('admin/config/search/facets/' . $facet_id . '/edit');

    $this->drupalGet('admin/config/search/facets');
  }

  /**
   * Tests creating a facet with an existing machine name.
   *
   * @param string $facet_name
   *   The name of the facet.
   * @param string $facet_type
   *   The type of facet to create.
   */
  protected function addFacetDuplicate($facet_name, $facet_type = 'type') {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_add_page = '/admin/config/search/facets/add-facet';
    $this->drupalGet($facet_add_page);

    $form_values = [
      'name' => $facet_name,
      'id' => $facet_id,
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
    ];

    $facet_source_configs['facet_source_configs[search_api:views_page__search_api_test_view__page_1][field_identifier]'] = $facet_type;

    // Try to submit a facet with a duplicate machine name after form rebuilding
    // via facet source submit.
    $this->drupalPostForm(NULL, $form_values, 'Configure facet source');
    $this->drupalPostForm(NULL, $form_values + $facet_source_configs, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    // Try to submit a facet with a duplicate machine name after form rebuilding
    // via facet source submit using AJAX.
    $this->submitForm($form_values, 'Configure facet source');
    $this->submitForm($form_values + $facet_source_configs, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
  }

  /**
   * Tests editing of a facet through the UI.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function editFacet($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_edit_page = '/admin/config/search/facets/' . $facet_id . '/settings';

    // Go to the facet edit page and make sure "edit facet %facet" is present.
    $this->drupalGet($facet_edit_page);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Facet settings for ' . $facet_name . ' facet');

    // Check if it's possible to change the machine name.
    $elements = $this->xpath('//form[@id="facets-facet-settings-form"]/div[contains(@class, "form-item-id")]/input[@disabled]');
    $this->assertEquals(count($elements), 1, 'Machine name cannot be changed.');

    // Change the facet name to add in "-2" to test editing of a facet works.
    $form_values = ['name' => $facet_name . ' - 2'];
    $this->drupalPostForm($facet_edit_page, $form_values, 'Save');

    // Make sure that the redirection back to the overview was successful and
    // the edited facet is shown on the overview page.
    $this->assertSession()->pageTextContains('Facet ' . $facet_name . ' - 2 has been updated.');

    // Make sure the "-2" suffix is still on the facet when editing a facet.
    $this->drupalGet($facet_edit_page);
    $this->assertSession()->responseContains('Facet settings for ' . $facet_name . ' - 2 facet');

    // Edit the form and change the facet's name back to the initial name.
    $form_values = ['name' => $facet_name];
    $this->drupalPostForm($facet_edit_page, $form_values, 'Save');

    // Make sure that the redirection back to the overview was successful and
    // the edited facet is shown on the overview page.
    $this->assertSession()->pageTextContains('Facet ' . $facet_name . ' has been updated.');

    $facet_edit_page = '/admin/config/search/facets/' . $facet_id . '/edit';
    $this->drupalGet($facet_edit_page);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('View Search API Test Fulltext search view, display Page');
  }

  /**
   * Deletes a facet through the UI that still has usages.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function deleteUsedFacet($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_delete_page = '/admin/config/search/facets/' . $facet_id . '/delete';

    // Go to the facet delete page and make the warning is shown.
    $this->drupalGet($facet_delete_page);
    $this->assertSession()->statusCodeEquals(200);

    // Check that the facet by testing for the message and the absence of the
    // facet name on the overview.
    $this->assertSession()->responseContains("The facet is currently used in a block and thus can't be removed. Remove the block first.");
  }

  /**
   * Deletes a facet through the UI.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function deleteUnusedFacet($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_delete_page = '/admin/config/search/facets/' . $facet_id . '/delete';
    $facet_overview = '/admin/config/search/facets';

    // Go to the facet delete page and make the warning is shown.
    $this->drupalGet($facet_delete_page);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("This action cannot be undone.");

    // Click the cancel link and see that we redirect to the overview page.
    $this->clickLink("Cancel");
    $this->assertSession()->addressEquals($facet_overview);

    // Back to the delete page.
    $this->drupalGet($facet_delete_page);

    // Actually submit the confirmation form.
    $this->drupalPostForm(NULL, [], 'Delete');

    // Check that the facet by testing for the message and the absence of the
    // facet name on the overview.
    $this->assertSession()->pageTextContains('The facet ' . $facet_name . ' has been deleted.');

    // Refresh the page because on the previous page the $facet_name is still
    // visible (in the message).
    $this->drupalGet($facet_overview);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains($facet_name);
  }

  /**
   * Add fields to Search API index.
   */
  protected function addFieldsToIndex() {
    $edit = [
      'fields[entity:node/nid][indexed]' => 1,
      'fields[entity:node/title][indexed]' => 1,
      'fields[entity:node/title][type]' => 'text',
      'fields[entity:node/title][boost]' => '21.0',
      'fields[entity:node/body][indexed]' => 1,
      'fields[entity:node/uid][indexed]' => 1,
      'fields[entity:node/uid][type]' => 'search_api_test_data_type',
    ];

    $this->drupalPostForm('admin/config/search/search-api/index/webtest_index/fields', $edit, 'Save changes');
    $this->assertSession()->pageTextContains('The changes were successfully saved.');
  }

  /**
   * Go to the Delete Facet Page using the facet name.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function goToDeleteFacetPage($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_delete_page = '/admin/config/search/facets/' . $facet_id . '/delete';

    // Go to the facet delete page and make the warning is shown.
    $this->drupalGet($facet_delete_page);
    $this->assertSession()->statusCodeEquals(200);
  }

}
