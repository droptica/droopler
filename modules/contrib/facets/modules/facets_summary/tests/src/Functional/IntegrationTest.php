<?php

namespace Drupal\Tests\facets_summary\Functional;

use Drupal\Tests\facets\Functional\FacetsTestBase;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\views\Views;

/**
 * Tests the overall functionality of the Facets summary admin UI.
 *
 * @group facets
 */
class IntegrationTest extends FacetsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets_summary',
  ];

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
   * Tests the overall functionality of the Facets summary admin UI.
   */
  public function testFramework() {
    $this->drupalGet('admin/config/search/facets');
    $this->assertSession()->pageTextNotContains('Facets Summary');

    $values = [
      'name' => 'Owl',
      'id' => 'owl',
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
    ];
    $this->drupalPostForm('admin/config/search/facets/add-facet-summary', $values, 'Save');
    $this->drupalPostForm(NULL, [], 'Save');

    $this->drupalGet('admin/config/search/facets');
    $this->assertSession()->pageTextContains('Facets Summary');
    $this->assertSession()->pageTextContains('Owl');

    $this->drupalGet('admin/config/search/facets/facet-summary/owl/edit');
    $this->assertSession()->pageTextContains('No facets found.');

    $this->createFacet('Llama', 'llama');
    $this->drupalGet('admin/config/search/facets');
    $this->assertSession()->pageTextContains('Llama');

    // Go back to the facet summary and check that the facets are not checked by
    // default and that they show up in the list here.
    $this->drupalGet('admin/config/search/facets/facet-summary/owl/edit');
    $this->assertSession()->pageTextNotContains('No facets found.');
    $this->assertSession()->pageTextContains('Llama');
    $this->assertSession()->checkboxNotChecked('edit-facets-llama-checked');

    // Post the form and check that no facets are checked after saving the form.
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertSession()->checkboxNotChecked('edit-facets-llama-checked');

    // Enable a facet and check it's status after saving.
    $this->drupalPostForm(NULL, ['facets[llama][checked]' => TRUE], 'Save');
    $this->assertSession()->checkboxChecked('edit-facets-llama-checked');

    $this->configureShowCountProcessor();
    $this->configureResetFacetsProcessor();
  }

  /**
   * Tests with multiple facets.
   *
   * Includes a regression test for #2841357
   */
  public function testMultipleFacets() {
    // Create facets.
    $this->createFacet('Giraffe', 'giraffe', 'keywords');
    // Clear all the caches between building the 2 facets - because things fail
    // otherwise.
    $this->resetAll();
    $this->createFacet('Llama', 'llama');

    // Add a summary.
    $values = [
      'name' => 'Owlß',
      'id' => 'owl',
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
    ];
    $this->drupalPostForm('admin/config/search/facets/add-facet-summary', $values, 'Save');
    $this->drupalPostForm(NULL, [], 'Save');

    // Edit the summary and enable the giraffe's.
    $summaries = [
      'facets[giraffe][checked]' => TRUE,
      'facets[giraffe][label]' => 'Summary giraffe',
    ];
    $this->drupalPostForm('admin/config/search/facets/facet-summary/owl/edit', $summaries, 'Save');

    $block = [
      'region' => 'footer',
      'id' => str_replace('_', '-', 'owl'),
      'weight' => 50,
    ];
    $block = $this->drupalPlaceBlock('facets_summary_block:owl', $block);

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertSession()->pageTextContains($block->label());
    $this->assertFacetBlocksAppear();

    $this->clickLink('apple');
    $list_items = $this->getSession()
      ->getPage()
      ->findById('block-' . $block->id())
      ->findAll('css', 'li');
    $this->assertCount(1, $list_items);

    $this->clickLink('item');
    $list_items = $this->getSession()
      ->getPage()
      ->findById('block-' . $block->id())
      ->findAll('css', 'li');
    $this->assertCount(1, $list_items);

    // Edit the summary and enable the giraffe's.
    $summaries = [
      'facets[giraffe][checked]' => TRUE,
      'facets[giraffe][label]' => 'Summary giraffe',
      'facets[llama][checked]' => TRUE,
      'facets[llama][label]' => 'Summary llama',
    ];
    $this->drupalPostForm('admin/config/search/facets/facet-summary/owl/edit', $summaries, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertSession()->pageTextContains($block->label());
    $this->assertFacetBlocksAppear();

    $this->clickLink('apple');
    $list_items = $this->getSession()
      ->getPage()
      ->findById('block-' . $block->id())
      ->findAll('css', 'li');
    $this->assertCount(1, $list_items);

    $this->clickLink('item');
    $list_items = $this->getSession()
      ->getPage()
      ->findById('block-' . $block->id())
      ->findAll('css', 'li');
    $this->assertCount(2, $list_items);

    $this->checkShowCountProcessor();
    $this->checkResetFacetsProcessor();
  }

  /**
   * Tests "Show a summary of all selected facets".
   *
   * Regression test for https://www.drupal.org/node/2878851.
   */
  public function testShowSummary() {
    // Create facets.
    $this->createFacet('Giraffe', 'giraffe', 'keywords');
    // Clear all the caches between building the 2 facets - because things fail
    // otherwise.
    $this->resetAll();
    $this->createFacet('Llama', 'llama');

    // Add a summary.
    $values = [
      'name' => 'Owlß',
      'id' => 'owl',
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
    ];
    $this->drupalPostForm('admin/config/search/facets/add-facet-summary', $values, 'Save');

    // Edit the summary and enable the facets.
    $summaries = [
      'facets[giraffe][checked]' => TRUE,
      'facets[giraffe][label]' => 'Summary giraffe',
      'facets[llama][checked]' => TRUE,
      'facets[llama][label]' => 'Summary llama',
      'facets_summary_settings[show_summary][status]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $summaries, 'Save');

    $block = [
      'region' => 'footer',
      'id' => str_replace('_', '-', 'owl'),
      'weight' => 50,
    ];
    $block = $this->drupalPlaceBlock('facets_summary_block:owl', $block);

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 5 search results');
    $this->clickLink('item');

    /** @var \Behat\Mink\Element\NodeElement[] $list_items */
    $list_items = $this->getSession()
      ->getPage()
      ->findById('block-' . $block->id())
      ->findAll('css', 'li');
    $this->assertCount(2, $list_items);
    $this->assertEquals('Summary llama: item', $list_items[0]->getText());
    $this->assertEquals('(-) item', $list_items[1]->getText());
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
    $values = [
      'name' => $name,
      'id' => $id,
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
    ];
    $this->drupalPostForm('admin/config/search/facets/add-facet-summary', $values, 'Save');
    $this->assertSession()->pageTextContains('Caching of view Search API Test Fulltext search view has been disabled.');

    // Check the view's cache settings again to see if they've been updated.
    $view = Views::getView('search_api_test_view');
    $view->setDisplay('page_1');
    $current_cache = $view->display_handler->getOption('cache');
    $this->assertEquals('none', $current_cache['type']);
  }

  /**
   * Tests counts for summaries.
   *
   * @see https://www.drupal.org/node/2873523
   */
  public function testCount() {
    // Create facets.
    $this->createFacet('Otter', 'otter', 'keywords');
    // Clear all the caches between building the 2 facets - because things fail
    // otherwise.
    $this->resetAll();
    $this->createFacet('Wolverine', 'wolverine');

    // Make sure the numbers are shown with the facets.
    $edit = [
      'widget' => 'links',
      'widget_config[show_numbers]' => '1',
    ];
    $this->drupalPostForm('admin/config/search/facets/otter/edit', $edit, 'Save');
    $this->drupalPostForm('admin/config/search/facets/wolverine/edit', $edit, 'Save');

    // Add a summary.
    $values = [
      'name' => 'Mustelidae',
      'id' => 'mustelidae',
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
    ];
    $this->drupalPostForm('admin/config/search/facets/add-facet-summary', $values, 'Save');

    // Configure the summary to hide the count.
    $summaries = [
      'facets[otter][checked]' => TRUE,
      'facets[otter][label]' => 'Summary giraffe',
      'facets[otter][show_count]' => FALSE,
      'facets[wolverine][checked]' => TRUE,
      'facets[wolverine][label]' => 'Summary llama',
      'facets[wolverine][show_count]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $summaries, 'Save');

    // Place the block.
    $block = [
      'region' => 'footer',
      'id' => str_replace('_', '-', 'owl'),
      'weight' => 50,
    ];
    $summary_block = $this->drupalPlaceBlock('facets_summary_block:mustelidae', $block);

    $this->drupalGet('search-api-test-fulltext');
    $webAssert = $this->assertSession();
    $webAssert->pageTextContains('Displaying 5 search results');
    $this->assertFacetBlocksAppear();
    $webAssert->pageTextContains($summary_block->label());

    $this->assertFacetLabel('article (2)');
    $this->assertFacetLabel('apple (2)');

    $summaries = [
      'facets[otter][show_count]' => TRUE,
      'facets[wolverine][show_count]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/search/facets/facet-summary/mustelidae/edit', $summaries, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $webAssert = $this->assertSession();
    $webAssert->pageTextContains('Displaying 5 search results');
    $this->assertFacetBlocksAppear();
    $webAssert->pageTextContains($summary_block->label());

    $this->assertFacetLabel('article (2)');
    $this->assertFacetLabel('apple (2)');
  }

  /**
   * Tests for deleting a block.
   */
  public function testBlockDelete() {
    $name = 'Owl';
    $id = 'owl';

    $values = [
      'name' => $name,
      'id' => $id,
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
    ];
    $this->drupalPostForm('admin/config/search/facets/add-facet-summary', $values, 'Save');
    $this->drupalPostForm(NULL, [], 'Save');

    $block_settings = [
      'region' => 'footer',
      'id' => $id,
    ];
    $block = $this->drupalPlaceBlock('facets_summary_block:' . $id, $block_settings);

    $this->drupalGet('admin/structure/block');
    $this->assertSession()->pageTextContains($block->label());

    $this->drupalGet('admin/structure/block/library/classy');
    $this->assertSession()->pageTextContains($name);

    // Check for the warning message that additional config entities will be
    // deleted if the facet summary is removed.
    $this->drupalGet('admin/config/search/facets/facet-summary/' . $id . '/delete');
    $this->assertSession()->pageTextContains('The listed configuration will be deleted.');
    $this->assertSession()->pageTextContains($block->label());
    $this->drupalPostForm(NULL, [], 'Delete');

    $this->drupalGet('admin/structure/block/library/classy');
    $this->assertSession()->pageTextNotContains($name);
  }

  /**
   * Tests configuring show_count processor.
   */
  protected function configureShowCountProcessor() {
    $this->assertSession()->checkboxNotChecked('edit-facets-summary-settings-show-count-status');
    $this->drupalPostForm(NULL, ['facets_summary_settings[show_count][status]' => TRUE], 'Save');
    $this->assertSession()->checkboxChecked('edit-facets-summary-settings-show-count-status');
    $this->assertSession()->pageTextContains(t('Facets Summary Owl has been updated.'));
  }

  /**
   * Tests configuring reset facets processor.
   */
  protected function configureResetFacetsProcessor() {
    $this->assertSession()->checkboxNotChecked('edit-facets-summary-settings-reset-facets-status');
    $this->drupalPostForm(NULL, ['facets_summary_settings[reset_facets][status]' => TRUE], 'Save');
    $this->assertSession()->checkboxChecked('edit-facets-summary-settings-reset-facets-status');
    $this->assertSession()->pageTextContains(t('Facets Summary Owl has been updated.'));

    $this->assertSession()->fieldExists('facets_summary_settings[reset_facets][settings][link_text]');
    $this->drupalPostForm(NULL, ['facets_summary_settings[reset_facets][settings][link_text]' => 'Reset facets'], 'Save');
    $this->assertSession()->pageTextContains(t('Facets Summary Owl has been updated.'));
    $this->assertSession()->fieldValueEquals('facets_summary_settings[reset_facets][settings][link_text]', 'Reset facets');
  }

  /**
   * Tests show_count processor.
   */
  protected function checkShowCountProcessor() {
    // Create new facets summary.
    FacetsSummary::create([
      'id' => 'show_count',
      'name' => 'Show count summary',
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
      'facets' => [
        'giraffe' => [
          'checked' => 1,
          'label' => 'Giraffe',
          'separator' => ',',
          'weight' => 0,
          'show_count' => 0,
        ],
        'llama' => [
          'checked' => 1,
          'label' => 'Llama',
          'separator' => ',',
          'weight' => 0,
          'show_count' => 0,
        ],
      ],
      'processor_configs' => [
        'show_count' => [
          'processor_id' => 'show_count',
          'weights' => ['build' => -10],
        ],
      ],
    ])->save();

    // Clear the cache after the new facet summary entity was created.
    $this->resetAll();

    // Place a block and test show_count processor.
    $blockConfig = [
      'region' => 'footer',
      'id' => 'show-count',
      'label' => 'show-count-block',
    ];
    $this->drupalPlaceBlock('facets_summary_block:show_count', $blockConfig);
    $this->drupalGet('search-api-test-fulltext');

    $this->assertSession()->pageTextContains('5 results found');

    $this->clickLink('apple');
    $this->assertSession()->pageTextContains('2 results found');

    $this->clickLink('item');
    $this->assertSession()->pageTextContains('1 result found');
  }

  /**
   * Tests reset facets processor.
   */
  protected function checkResetFacetsProcessor() {
    // Create new facets summary.
    FacetsSummary::create([
      'id' => 'reset_facets',
      'name' => t('Reset facets summary'),
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
      'facets' => [
        'giraffe' => [
          'checked' => 1,
          'label' => 'Giraffe',
          'separator' => ',',
          'weight' => 0,
          'show_count' => 0,
        ],
        'llama' => [
          'checked' => 1,
          'label' => 'Llama',
          'separator' => ',',
          'weight' => 0,
          'show_count' => 0,
        ],
      ],
      'processor_configs' => [
        'reset_facets' => [
          'processor_id' => 'reset_facets',
          'weights' => ['build' => -10],
          'settings' => ['link_text' => 'Reset facets'],
        ],
      ],
    ])->save();

    // Clear the cache after the new facet summary entity was created.
    $this->resetAll();

    // Place a block and test reset facets processor.
    $blockConfig = [
      'label' => 'Reset block',
      'region' => 'footer',
      'id' => 'reset-facets',
    ];
    $this->drupalPlaceBlock('facets_summary_block:reset_facets', $blockConfig);
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->addressEquals('/search-api-test-fulltext');

    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertSession()->pageTextNotContains('Reset facets');

    $this->clickLink('apple');
    $this->assertSession()->pageTextContains('Displaying 2 search results');
    $this->assertSession()->pageTextContains('Reset facets');

    $this->clickLink('Reset facets');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->assertSession()->pageTextNotContains('Reset facets');
  }

  /**
   * Tests  first facet doesn't have any item in for a particular filter.
   */
  public function testEmptyFacetLinks() {
    // Create facets.
    $this->createFacet('Kepler-442b', 'category', 'category');
    // Clear all the caches between building the 2 facets - because things fail
    // otherwise.
    $this->createFacet('Kepler-438b', 'keywords', 'keywords');
    $this->resetAll();

    // Create a new item, make sure it doesn't have a "keywords" property at
    // all.
    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');
    $this->entities[] = $entity_test_storage->create([
      'name' => 'Test with no category',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['rotten orange'],
    ])->save();

    $this->indexItems($this->indexId);

    // Add a facets summary entity.
    $values = [
      'name' => 'Kepler planets',
      'id' => 'kepler',
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
    ];
    $this->drupalPostForm('admin/config/search/facets/add-facet-summary', $values, 'Save');

    // Place the block.
    $block = [
      'region' => 'footer',
      'id' => 'kplanets',
      'weight' => -10,
    ];
    $summary_block = $this->drupalPlaceBlock('facets_summary_block:kepler', $block);

    // Enable the facets for the summary.
    $summaries = [
      'facets[category][checked]' => TRUE,
      'facets[category][weight]' => 0,
      'facets[keywords][checked]' => TRUE,
      'facets[keywords][weight]' => 1,
      'facets_summary_settings[reset_facets][status]' => 1,
      'facets_summary_settings[reset_facets][settings][link_text]' => 'Reset',
    ];
    $this->drupalPostForm('admin/config/search/facets/facet-summary/kepler/edit', $summaries, 'Save');

    // Go to the search view, and check that the summary, as well as the facets
    // are shown on the page.
    $this->drupalGet('search-api-test-fulltext');
    $web_assert = $this->assertSession();
    $web_assert->pageTextContains('Displaying 6 search results');
    $this->assertFacetBlocksAppear();
    $web_assert->pageTextContains($summary_block->label());

    // Filter on the item type.
    $this->clickLink('rotten orange');
    $web_assert->pageTextContains('Test with no category');
  }

  /**
   * Tests the reset facet link.
   *
   * @see https://www.drupal.org/project/facets/issues/2960137
   */
  public function testResetFacetLink() {
    $this->createFacet('Brasserie d\'Orval', 'orval', 'category');
    $this->resetAll();
    // Add a facets summary entity.
    $values = [
      'name' => 'Trappist beers',
      'id' => 'trappist',
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
    ];
    $this->drupalPostForm('admin/config/search/facets/add-facet-summary', $values, 'Save');

    // Place the block.
    $block = [
      'region' => 'footer',
      'id' => 'trappist',
      'weight' => -10,
    ];
    $summary_block = $this->drupalPlaceBlock('facets_summary_block:trappist', $block);

    // Enable the facets for the summary.
    $summaries = [
      'facets[orval][checked]' => TRUE,
      'facets[orval][weight]' => 0,
      'facets_summary_settings[reset_facets][status]' => 1,
      'facets_summary_settings[reset_facets][settings][link_text]' => 'Reset',
    ];
    $this->drupalPostForm('admin/config/search/facets/facet-summary/trappist/edit', $summaries, 'Save');

    // Go to the search view, and check that the summary, as well as the facets
    // are shown on the page.
    $this->drupalGet('search-api-test-fulltext');
    $web_assert = $this->assertSession();
    $web_assert->pageTextContains('Displaying 5 search results');
    $this->assertFacetBlocksAppear();
    $web_assert->pageTextContains($summary_block->label());

    $links = $this->xpath('//a[normalize-space(text())=:label]', [':label' => 'Reset']);
    $this->assertEmpty($links);
    $this->clickLink('article_category');
    $links = $this->xpath('//a[normalize-space(text())=:label]', [':label' => 'Reset']);
    $this->assertNotEmpty($links);
  }

}
