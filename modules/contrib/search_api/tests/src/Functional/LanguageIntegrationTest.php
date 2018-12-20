<?php

namespace Drupal\Tests\search_api\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Entity\Index;

/**
 * Tests the overall functionality of indexing specific logic.
 *
 * @group search_api
 */
class LanguageIntegrationTest extends SearchApiBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'search_api',
    'search_api_test',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add extra languages.
    ConfigurableLanguage::createFromLangcode('nl')->save();
    ConfigurableLanguage::createFromLangcode('xx-lolspeak')->save();

    // Create an index and server to work with.
    $this->getTestServer();
    $this->getTestIndex();

    // Log in, so we can test all the things.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests indexing with different language settings trough the UI.
   */
  public function testIndexSettings() {
    // Create 2 articles.
    $article1 = $this->drupalCreateNode(['type' => 'article']);
    $article2 = $this->drupalCreateNode(['type' => 'article']);

    // Those 2 new nodes should be added to the tracking table immediately.
    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(2, $tracked_items, 'Two items are tracked.');

    // Add translations.
    $translation = ['title' => 'test NL', 'body' => 'NL body'];
    $article1->addTranslation('nl', $translation)->save();
    $translation = ['title' => 'test2 NL', 'body' => 'NL body2'];
    $article2->addTranslation('nl', $translation)->save();
    $translation = ['title' => 'cats', 'body' => 'Cats test'];
    $article1->addTranslation('xx-lolspeak', $translation)->save();

    // The translations should be tracked as well, so we have a total of 5
    // indexed items.
    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(5, $tracked_items, 'Five items are tracked.');

    // Clear index.
    $this->drupalGet($this->getIndexPath());
    $this->submitForm([], 'Clear all indexed data');
    $this->submitForm([], 'Confirm');

    // Make sure all 5 items are successfully indexed.
    $this->drupalGet($this->getIndexPath());
    $this->submitForm([], 'Index now');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Successfully indexed 5 items');

    // Change the datasource to disallow indexing of dutch.
    $form_values = [
      'datasource_configs[entity:node][languages][default]' => 1,
      'datasource_configs[entity:node][languages][selected][nl]' => 1,
    ];
    $this->drupalGet($this->getIndexPath('edit'));
    $this->submitForm($form_values, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    // Make sure that we only have 3 indexed items now. The 2 original nodes
    // + 1 translation in lolspeak, the 2 dutch translations should be ignored.
    $this->drupalGet($this->getIndexPath());
    $this->submitForm([], 'Index now');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Successfully indexed 3 items');

    // Change the datasource to only allow indexing of dutch.
    $form_values = [
      'datasource_configs[entity:node][languages][default]' => 0,
      'datasource_configs[entity:node][languages][selected][nl]' => 1,
      'datasource_configs[entity:node][bundles][default]' => 0,
      'datasource_configs[entity:node][bundles][selected][article]' => 1,
    ];
    $this->drupalGet($this->getIndexPath('edit'));
    $this->submitForm($form_values, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->statusCodeEquals(200);
    // Completed 1 of 1.
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    // Make sure that we only have 2 index items. The only indexed items should
    // be the dutch translations.
    $this->drupalGet($this->getIndexPath());
    $this->submitForm([], 'Index now');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Successfully indexed 2 items');
  }

  /**
   * Counts the number of tracked items in the test index.
   *
   * @return int
   *   The number of tracked items in the test index.
   */
  protected function countTrackedItems() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load($this->indexId);
    return $index->getTrackerInstance()->getTotalItemsCount();
  }

}
