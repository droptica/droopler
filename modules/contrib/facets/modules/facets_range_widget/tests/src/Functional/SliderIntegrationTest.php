<?php

namespace Drupal\Tests\facets_range_widget\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Item\Field;
use Drupal\Tests\facets\Functional\FacetsTestBase;

/**
 * Tests the overall functionality of the Facets admin UI.
 *
 * @group facets
 */
class SliderIntegrationTest extends FacetsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'node',
    'search_api',
    'facets',
    'facets_range_widget',
    'block',
    'facets_search_api_dependency',
    'facets_query_processor',
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
  }

  /**
   * Tests slider widget.
   */
  public function testSliderWidget() {
    $this->createIntegerField();
    $id = 'owl';
    $this->createFacet('Owl widget.', $id, 'field_integer');

    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');

    $this->assertSession()->checkboxNotChecked('edit-facet-settings-slider-status');

    $this->drupalPostForm(NULL, ['widget' => 'slider'], 'Configure widget');
    $this->drupalPostForm(NULL, ['widget' => 'slider'], 'Save');

    $this->assertSession()->checkboxChecked('edit-facet-settings-slider-status');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetBlocksAppear();
    $this->assertSession()->pageTextContains('Displaying 12 search results');

    // Change the facet block.
    $url = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['f[0]' => 'owl:2']]);
    $this->drupalGet($url->setAbsolute()->toString());

    // Check that the results have changed to the correct amount of results.
    $this->assertSession()->pageTextContains('Displaying 1 search results');
    $this->assertSession()->pageTextContains('foo bar baz 2');

    // Change the facet block.
    $url = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['f[0]' => 'owl:4']]);
    $this->drupalGet($url->setAbsolute()->toString());

    // Check that the results have changed to the correct amount of results.
    $this->assertSession()->pageTextContains('Displaying 1 search results');
    $this->assertSession()->pageTextContains('foo bar baz 4');
  }

  /**
   * Create integer field.
   */
  protected function createIntegerField() {
    $index = $this->getIndex();

    // Create integer field.
    $field_name = 'field_integer';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'integer',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'item',
    ]);
    $field->save();

    // Create the field for search api.
    $intfield = new Field($index, $field_name);
    $intfield->setType('integer');
    $intfield->setPropertyPath($field_name);
    $intfield->setDatasourceId('entity:entity_test_mulrev_changed');
    $intfield->setLabel('IntegerField');

    // Add to field to the index.
    $index->addField($intfield);
    $index->save();
    $this->indexItems($this->indexId);

    // Add new entities.
    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');
    for ($i = 1; $i < 8; $i++) {
      $entity_test_storage->create([
        'name' => 'foo bar baz ' . $i,
        'body' => 'test ' . $i . ' test',
        'type' => 'item',
        'keywords' => ['orange'],
        'category' => 'item_category',
        'field_integer' => (bool) $i % 2 ? $i : $i + 1,
      ])->save();
    }

    // Index all the items.
    $this->indexItems($this->indexId);
  }

}
