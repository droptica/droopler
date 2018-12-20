<?php

namespace Drupal\Tests\facets\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the integration with the language module.
 *
 * @group facets
 */
class LanguageIntegrationTest extends FacetsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'search_api',
    'facets',
    'block',
    'facets_search_api_dependency',
    'language',
    'config_translation',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer search_api',
      'administer facets',
      'access administration pages',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer blocks',
      'translate configuration',
    ]);
    $this->drupalLogin($this->adminUser);

    ConfigurableLanguage::create([
      'id' => 'xx-lolspeak',
      'label' => 'Lolspeak',
    ])->save();
    ConfigurableLanguage::create([
      'id' => 'nl',
      'label' => 'Dutch',
    ])->save();
    ConfigurableLanguage::create([
      'id' => 'es',
      'label' => 'Spanish',
    ])->save();

    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertEquals($this->indexItems($this->indexId), 5, '5 items were indexed.');

    // Make absolutely sure the ::$blocks variable doesn't pass information
    // along between tests.
    $this->blocks = NULL;
  }

  /**
   * Tests that a facet works on a page with language prefix.
   *
   * @see https://www.drupal.org/node/2712557
   */
  public function testLanguageIntegration() {
    $facet_id = 'owl';
    $facet_name = 'Owl';
    $this->createFacet($facet_name, $facet_id);

    // Go to the search view with a language prefix and click on one of the
    // facets.
    $this->drupalGet('xx-lolspeak/search-api-test-fulltext');
    $this->assertSession()->pageTextContains('item');
    $this->assertSession()->pageTextContains('article');
    $this->clickLink('item');

    // Check that the language code is still in the url.
    $this->assertTrue(strpos($this->getUrl(), 'xx-lolspeak/'), 'Found the language code in the url');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('item');
    $this->assertSession()->pageTextContains('article');
  }

  /**
   * Tests that special characters work such as äüö work.
   *
   * @see https://www.drupal.org/node/2838247
   * @see https://www.drupal.org/node/2838697
   */
  public function testSpecialCharacters() {
    $id = 'water_bear';
    $name = 'Water bear';
    $this->createFacet($name, $id, 'keywords');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetBlocksAppear();
    $this->assertFacetLabel('orange');

    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');
    $entity_test_storage->create([
      'name' => 'special-chars 1',
      'body' => 'test test test',
      'type' => 'article',
      'keywords' => ['ƒäüö', 'test_key-word', 'special^%s', 'Key Word'],
      'category' => 'article_category',
    ])->save();
    $entity_test_storage->create([
      'name' => 'special-chars 2',
      'body' => 'test test test',
      'type' => 'article',
      'keywords' => ['ƒäüö', 'special^%s', 'aáå'],
      'category' => 'article_category',
    ])->save();
    $this->assertEquals(2, $this->indexItems($this->indexId), '2 items were indexed.');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetBlocksAppear();
    $this->assertFacetLabel('orange');
    $this->assertFacetLabel('ƒäüö');
    $this->assertFacetLabel('aáå');
    $this->assertFacetLabel('special^%s');
    $this->assertFacetLabel('test_key-word');
    $this->assertFacetLabel('Key Word');
  }

  /**
   * Tests the url alias translation.
   *
   * @see https://www.drupal.org/node/2893374
   */
  public function testUrlAliasTranslation() {
    $facet_id = 'barn_owl';
    $facet_name = 'Barn owl';
    $this->createFacet($facet_name, $facet_id);

    // Go to the search view with a language prefix and click on one of the
    // facets.
    $this->drupalGet('xx-lolspeak/search-api-test-fulltext');
    $this->assertFacetBlocksAppear();
    $this->clickLink('item');

    // Check that the language code is still in the url.
    $this->assertTrue((bool) strpos($this->getUrl(), 'xx-lolspeak/'), 'Found the language code in the url');
    $this->assertTrue((bool) strpos($this->getUrl(), 'barn_owl'), 'Found the facet in the url');

    // Translate the facet.
    $this->drupalGet('admin/config/search/facets/' . $facet_id . '/edit/translate/xx-lolspeak/add');
    $this->drupalPostForm(NULL, ['translation[config_names][facets.facet.barn_owl][url_alias]' => 'tyto_alba'], 'Save translation');
    $this->drupalGet('admin/config/search/facets/' . $facet_id . '/edit/translate/nl/add');
    $this->drupalPostForm(NULL, ['translation[config_names][facets.facet.barn_owl][url_alias]' => 'uil'], 'Save translation');
    $this->drupalGet('admin/config/search/facets/' . $facet_id . '/edit/translate/es/add');
    $this->drupalPostForm(NULL, ['translation[config_names][facets.facet.barn_owl][url_alias]' => 'buho'], 'Save translation');

    // Go to the search view again and check that we now have the translated
    // facet in the url.
    $this->drupalGet('xx-lolspeak/search-api-test-fulltext');
    $this->assertFacetBlocksAppear();
    $this->clickLink('item');
    $this->assertTrue((bool) strpos($this->getUrl(), 'xx-lolspeak/'), 'Found the language code in the url');
    $this->assertTrue((bool) strpos($this->getUrl(), 'tyto_alba'), 'Found the facet in the url');

    \Drupal::service('module_installer')->install(['locale']);
    $block = $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, [
      'id' => 'test_language_block',
    ]);

    $this->drupalGet('xx-lolspeak/search-api-test-fulltext');
    $this->assertSession()->pageTextContains($block->label());
    $this->clickLink('item');

    /** @var \Behat\Mink\Element\NodeElement[] $links */
    $links = $this->findFacetLink('item');
    $this->assertEquals('is-active', $links[0]->getParent()->getAttribute('class'));

    $this->clickLink('English');
    /** @var \Behat\Mink\Element\NodeElement[] $links */
    $links = $this->findFacetLink('item');
    $this->assertEquals('is-active', $links[0]->getParent()->getAttribute('class'));
    $this->assertFalse((bool) strpos($this->getUrl(), 'xx-lolspeak/'), 'Found the language code in the url');
    $this->assertFalse((bool) strpos($this->getUrl(), 'tyto_alba'), 'Found the facet in the url');
    $this->assertTrue((bool) strpos($this->getUrl(), 'barn_owl'), 'Found the facet in the url');

    $this->clickLink('Lolspeak');
    /** @var \Behat\Mink\Element\NodeElement[] $links */
    $links = $this->findFacetLink('item');
    $this->assertEquals('is-active', $links[0]->getParent()->getAttribute('class'));
    $this->assertTrue((bool) strpos($this->getUrl(), 'xx-lolspeak/'), 'Found the language code in the url');
    $this->assertTrue((bool) strpos($this->getUrl(), 'tyto_alba'), 'Found the facet in the url');
    $this->assertFalse((bool) strpos($this->getUrl(), 'barn_owl'), 'Found the facet in the url');

    $this->clickLink('Dutch');
    /** @var \Behat\Mink\Element\NodeElement[] $links */
    $links = $this->findFacetLink('item');
    $this->assertEquals('is-active', $links[0]->getParent()->getAttribute('class'));
    $this->assertTrue((bool) strpos($this->getUrl(), 'nl/'), 'Found the language code in the url');
    $this->assertTrue((bool) strpos($this->getUrl(), 'uil'), 'Found the facet in the url');

    $this->clickLink('Spanish');
    /** @var \Behat\Mink\Element\NodeElement[] $links */
    $links = $this->findFacetLink('item');
    $this->assertEquals('is-active', $links[0]->getParent()->getAttribute('class'));
    $this->assertTrue((bool) strpos($this->getUrl(), 'es/'), 'Found the language code in the url');
    $this->assertTrue((bool) strpos($this->getUrl(), 'buho'), 'Found the facet in the url');

    $this->clickLink('English');
    /** @var \Behat\Mink\Element\NodeElement[] $links */
    $links = $this->findFacetLink('item');
    $this->assertEquals('is-active', $links[0]->getParent()->getAttribute('class'));
    $this->assertTrue((bool) strpos($this->getUrl(), 'barn_owl'), 'Found the facet in the url');

  }

  /**
   * Tests facets where the count is different per language.
   *
   * @see https://www.drupal.org/node/2827808
   */
  public function testLanguageDifferences() {
    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');
    $entity_test_storage->create([
      'name' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange', 'lol'],
      'category' => 'item_category',
      'langcode' => 'xx-lolspeak',
    ])->save();
    $entity_test_storage->create([
      'name' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange', 'rofl'],
      'category' => 'item_category',
      'langcode' => 'xx-lolspeak',
    ])->save();

    $id = 'water_bear';
    $this->createFacet('Water bear', $id, 'keywords');

    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/edit');

    $this->assertEquals(2, $this->indexItems($this->indexId), '2 items were indexed.');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetBlocksAppear();
    $this->assertSession()->pageTextContains('orange');
    $this->assertSession()->pageTextContains('grape');
    $this->assertSession()->pageTextContains('rofl');

    $this->drupalPostForm(NULL, ['language' => 'xx-lolspeak'], 'Search');
    $this->assertFacetBlocksAppear();
    $this->assertSession()->pageTextContains('orange');
    $this->assertSession()->pageTextContains('rofl');
    $this->assertSession()->pageTextNotContains('grape');

    $this->drupalPostForm(NULL, ['language' => 'en'], 'Search');
    $this->assertFacetBlocksAppear();
    $this->assertSession()->pageTextContains('orange');
    $this->assertSession()->pageTextContains('grape');
    $this->assertSession()->pageTextNotContains('rofl');
  }

  /**
   * Tests the admin translation screen.
   */
  public function testAdminTranslation() {
    $id = 'water_bear';
    $this->createFacet('Water bear', $id);
    // Translate the facet.
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit/translate/xx-lolspeak/add');
    $this->drupalPostForm(NULL, ['translation[config_names][facets.facet.water_bear][name]' => 'Tardigrade'], 'Save translation');

    $this->drupalGet('admin/config/search/facets');
    $this->assertSession()->pageTextContains('Water bear');
    $this->drupalGet('xx-lolspeak/admin/config/search/facets');
    $this->assertSession()->pageTextContains('Tardigrade');
  }

}
