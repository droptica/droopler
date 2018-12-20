<?php

namespace Drupal\Tests\facets_summary\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\search_api\Item\Field;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\facets\Functional\FacetsTestBase;

/**
 * Tests the hierarchical facets implementation.
 *
 * @group facets
 */
class HierarchicalFacetIntegrationTest extends FacetsTestBase {

  use TaxonomyTestTrait;
  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets_summary',
  ];

  /**
   * Drupal vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * The field name for the referenced term.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Uri to the facets edit page.
   *
   * @var string
   */
  protected $facetEditPage;

  /**
   * An array of taxonomy terms.
   *
   * @var \Drupal\taxonomy\Entity\Term[]
   */
  protected $parents = [];

  /**
   * An array of taxonomy terms.
   *
   * @var \Drupal\taxonomy\Entity\Term[]
   */
  protected $terms = [];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Make absolutely sure the ::$blocks variable doesn't pass information
    // along between tests.
    $this->blocks = NULL;

    $this->drupalLogin($this->adminUser);

    // Create hierarchical terms in a new vocabulary.
    $this->vocabulary = $this->createVocabulary();
    $this->createHierarchialTermStructure();

    // Default content that is extended with a term reference field below.
    $this->setUpExampleStructure();

    // Create a taxonomy_term_reference field on the article and item.
    $this->fieldName = 'hierarchy_field';
    $fieldLabel = 'Hierarchy field';

    $this->createEntityReferenceField('entity_test_mulrev_changed', 'article', $this->fieldName, $fieldLabel, 'taxonomy_term');
    $this->createEntityReferenceField('entity_test_mulrev_changed', 'item', $this->fieldName, $fieldLabel, 'taxonomy_term');

    $this->insertExampleContent();

    // Add fields to index.
    $index = $this->getIndex();

    // Index the taxonomy and entity reference fields.
    $term_field = new Field($index, $this->fieldName);
    $term_field->setType('integer');
    $term_field->setPropertyPath($this->fieldName);
    $term_field->setDatasourceId('entity:entity_test_mulrev_changed');
    $term_field->setLabel($fieldLabel);
    $index->addField($term_field);

    $index->save();
    $this->indexItems($this->indexId);

    $facet_name = 'hierarchical facet';
    $facet_id = 'hierarchical_facet';
    $this->facetEditPage = 'admin/config/search/facets/' . $facet_id . '/edit';

    $this->createFacet($facet_name, $facet_id, $this->fieldName);
  }

  /**
   * Test the hierarchical facets functionality.
   */
  public function testHierarchicalFacet() {
    // Verify that the link to the index processors settings page is available.
    $this->drupalGet($this->facetEditPage);
    $this->clickLink('Search api index processor configuration');
    $this->assertSession()->statusCodeEquals(200);

    // Enable hierarchical facets and translation of entity ids to its names for
    // a better readability.
    $this->drupalGet($this->facetEditPage);
    $edit = [
      'facet_settings[use_hierarchy]' => '1',
      'facet_settings[translate_entity][status]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $values = [
      'name' => 'Owl',
      'id' => 'owl',
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
    ];
    $this->drupalPostForm('admin/config/search/facets/add-facet-summary', $values, 'Save');
    $this->drupalPostForm(NULL, [], 'Save');

    $block = [
      'region' => 'footer',
      'id' => str_replace('_', '-', 'owl'),
      'weight' => 50,
    ];
    $block = $this->drupalPlaceBlock('facets_summary_block:owl', $block);

    // Child elements should be collapsed and invisible.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetBlocksAppear();
    $this->assertFacetLabel('Parent 1');
    $this->assertFacetLabel('Parent 2');
    $this->assertSession()->linkNotExists('Child 1');
    $this->assertSession()->linkNotExists('Child 2');
    $this->assertSession()->linkNotExists('Child 3');
    $this->assertSession()->linkNotExists('Child 4');

    $this->assertSession()->pageTextContains($block->label());

    // Click the first parent and make sure its children are visible.
    $this->clickLink('Parent 1');
    $this->assertFacetBlocksAppear();
    $this->checkFacetIsActive('Parent 1');
    $this->assertFacetLabel('Child 1');
    $this->assertFacetLabel('Child 2');
    $this->assertSession()->linkNotExists('Child 3');
    $this->assertSession()->linkNotExists('Child 4');

    $this->assertSession()->pageTextContains($block->label());
  }

  /**
   * Setup a term structure for our test.
   */
  protected function createHierarchialTermStructure() {
    // Generate 2 parent terms.
    foreach (['Parent 1', 'Parent 2'] as $name) {
      $this->parents[$name] = Term::create([
        'name' => $name,
        'description' => '',
        'vid' => $this->vocabulary->id(),
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);
      $this->parents[$name]->save();
    }

    // Generate 4 child terms.
    foreach (range(1, 4) as $i) {
      $this->terms[$i] = Term::create([
        'name' => sprintf('Child %d', $i),
        'description' => '',
        'vid' => $this->vocabulary->id(),
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);
      $this->terms[$i]->save();
    }

    // Build up the hierarchy.
    $this->terms[1]->parent = [$this->parents['Parent 1']->id()];
    $this->terms[1]->save();

    $this->terms[2]->parent = [$this->parents['Parent 1']->id()];
    $this->terms[2]->save();

    $this->terms[3]->parent = [$this->parents['Parent 2']->id()];
    $this->terms[3]->save();

    $this->terms[4]->parent = [$this->parents['Parent 2']->id()];
    $this->terms[4]->save();
  }

  /**
   * Creates several test entities with the term-reference field.
   */
  protected function insertExampleContent() {
    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');

    $this->entities[1] = $entity_test_storage->create([
      'name' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange'],
      'category' => 'item_category',
      $this->fieldName => [$this->parents['Parent 1']->id()],
    ]);
    $this->entities[1]->save();

    $this->entities[2] = $entity_test_storage->create([
      'name' => 'foo test',
      'body' => 'bar test',
      'type' => 'item',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
      $this->fieldName => [$this->parents['Parent 2']->id()],
    ]);
    $this->entities[2]->save();

    $this->entities[3] = $entity_test_storage->create([
      'name' => 'bar',
      'body' => 'test foobar',
      'type' => 'item',
      $this->fieldName => [$this->terms[1]->id()],
    ]);
    $this->entities[3]->save();

    $this->entities[4] = $entity_test_storage->create([
      'name' => 'foo baz',
      'body' => 'test test test',
      'type' => 'article',
      'keywords' => ['apple', 'strawberry', 'grape'],
      'category' => 'article_category',
      $this->fieldName => [$this->terms[2]->id()],
    ]);
    $this->entities[4]->save();

    $this->entities[5] = $entity_test_storage->create([
      'name' => 'bar baz',
      'body' => 'foo',
      'type' => 'article',
      'keywords' => ['orange', 'strawberry', 'grape', 'banana'],
      'category' => 'article_category',
      $this->fieldName => [$this->terms[3]->id()],
    ]);
    $this->entities[5]->save();

    $this->entities[6] = $entity_test_storage->create([
      'name' => 'bar baz',
      'body' => 'foo',
      'type' => 'article',
      'keywords' => ['orange', 'strawberry', 'grape', 'banana'],
      'category' => 'article_category',
      $this->fieldName => [$this->terms[4]->id()],
    ]);
    $this->entities[6]->save();
  }

}
