<?php

namespace Drupal\Tests\facets\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\search_api\Item\Field;
use Drupal\taxonomy\Entity\Term;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Tests the hierarchical facets implementation.
 *
 * @group facets
 */
class HierarchicalFacetIntegrationTest extends FacetsTestBase {

  use TaxonomyTestTrait;
  use EntityReferenceTestTrait;

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

    $this->drupalLogin($this->adminUser);

    // Create hierarchical terms in a new vocabulary.
    $this->vocabulary = $this->createVocabulary();
    $this->createHierarchialTermStructure();

    // Default content that is extended with a term reference field below.
    $this->setUpExampleStructure();

    // Create a taxonomy_term_reference field on the article and item.
    $this->fieldName = 'tax_ref_field';
    $fieldLabel = 'Taxonomy reference field';

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

    // Make absolutely sure the ::$blocks variable doesn't pass information
    // along between tests.
    $this->blocks = NULL;
  }

  /**
   * Test the hierarchical facets functionality.
   */
  public function testHierarchicalFacet() {
    $this->verifyUseHierarchyOption();
    $this->verifyExpandHierarchyOption();
    $this->verifyEnableParentWhenChildGetsDisabledOption();
  }

  /**
   * Verify the backend option "Use hierarchy" is working.
   */
  protected function verifyUseHierarchyOption() {
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

    // Child elements should be collapsed and invisible.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('Parent 1');
    $this->assertFacetLabel('Parent 2');
    $this->assertSession()->linkNotExists('Child 1');
    $this->assertSession()->linkNotExists('Child 2');
    $this->assertSession()->linkNotExists('Child 3');
    $this->assertSession()->linkNotExists('Child 4');

    // Click the first parent and make sure its children are visible.
    $this->clickLink('Parent 1');
    $this->checkFacetIsActive('Parent 1');
    $this->assertFacetLabel('Child 1');
    $this->assertFacetLabel('Child 2');
    $this->assertSession()->linkNotExists('Child 3');
    $this->assertSession()->linkNotExists('Child 4');
  }

  /**
   * Verify the "Always expand hierarchy" option is working.
   */
  protected function verifyExpandHierarchyOption() {
    // Expand the hierarchy and verify that all items are visible initially.
    $this->drupalGet($this->facetEditPage);
    $edit = [
      'facet_settings[expand_hierarchy]' => '1',
      'facet_settings[use_hierarchy]' => '1',
      'facet_settings[translate_entity][status]' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');

    $this->assertFacetLabel('Parent 1');
    $this->assertFacetLabel('Parent 2');
    $this->assertFacetLabel('Child 1');
    $this->assertFacetLabel('Child 2');
    $this->assertFacetLabel('Child 3');
    $this->assertFacetLabel('Child 4');
  }

  /**
   * Tests sorting of hierarchy.
   */
  public function testHierarchySorting() {
    // Expand the hierarchy and verify that all items are visible initially.
    $edit = [
      'facet_settings[expand_hierarchy]' => '1',
      'facet_settings[use_hierarchy]' => '1',
      'facet_settings[translate_entity][status]' => '1',
      'facet_sorting[display_value_widget_order][status]' => '1',
      'facet_sorting[display_value_widget_order][settings][sort]' => 'ASC',
      'facet_sorting[count_widget_order][status]' => '0',
      'facet_sorting[active_widget_order][status]' => '0',
    ];
    $this->drupalPostForm($this->facetEditPage, $edit, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('Parent 1', 'Parent 2');
    $this->assertStringPosition('Child 1', 'Child 2');
    $this->assertStringPosition('Child 2', 'Child 3');
    $this->assertStringPosition('Child 3', 'Child 4');

    $edit = [
      'facet_sorting[display_value_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm($this->facetEditPage, $edit, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('Parent 2', 'Parent 1');
    $this->assertStringPosition('Child 4', 'Child 3');
    $this->assertStringPosition('Child 3', 'Child 2');
    $this->assertStringPosition('Child 2', 'Child 1');
  }

  /**
   * Tests sorting by weight of a taxonomy term.
   */
  public function testWeightSort() {
    $edit = [
      'facet_settings[translate_entity][status]' => '1',
      'facet_sorting[term_weight_widget_order][status]' => '1',
    ];
    $this->drupalPostForm($this->facetEditPage, $edit, 'Save');

    $this->parents['Parent 1']->setWeight(15);
    $this->parents['Parent 1']->save();
    $this->parents['Parent 2']->setWeight(25);
    $this->parents['Parent 2']->save();

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('Parent 1');
    $this->assertFacetLabel('Parent 2');
    $this->assertStringPosition('Parent 1', 'Parent 2');

    $this->parents['Parent 2']->setWeight(5);
    $this->parents['Parent 2']->save();

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('Parent 1');
    $this->assertFacetLabel('Parent 2');
    $this->assertStringPosition('Parent 2', 'Parent 1');
  }

  /**
   * Verify the "Enable parent when child gets disabled" option is working.
   */
  protected function verifyEnableParentWhenChildGetsDisabledOption() {
    // Make sure the option is disabled initially.
    $this->drupalGet($this->facetEditPage);
    $edit = [
      'facet_settings[expand_hierarchy]' => '1',
      'facet_settings[enable_parent_when_child_gets_disabled]' => FALSE,
      'facet_settings[use_hierarchy]' => '1',
      'facet_settings[translate_entity][status]' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');

    // Enable a child under Parent 2.
    $this->clickLink('Child 4');
    $this->checkFacetIsActive('Child 4');
    $this->checkFacetIsNotActive('Parent 2');

    // Uncheck the facet again.
    $this->clickLink('(-) Child 4');
    $this->checkFacetIsNotActive('Child 4');
    $this->checkFacetIsNotActive('Parent 2');

    // Enable the option.
    $this->drupalGet($this->facetEditPage);
    $edit = [
      'facet_settings[expand_hierarchy]' => '1',
      'facet_settings[enable_parent_when_child_gets_disabled]' => '1',
      'facet_settings[use_hierarchy]' => '1',
      'facet_settings[translate_entity][status]' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');

    $this->clickLink('Child 4');
    $this->checkFacetIsActive('Child 4');
    $this->clickLink('Child 3');
    $this->checkFacetIsActive('Child 3');
    $this->checkFacetIsActive('Child 4');
    $this->checkFacetIsNotActive('Parent 2');

    $this->clickLink('(-) Child 4');
    $this->checkFacetIsActive('Child 3');
    $this->checkFacetIsNotActive('Child 4');
    $this->checkFacetIsNotActive('Parent 2');

    $this->clickLink('(-) Child 3');
    $this->checkFacetIsNotActive('Child 3');
    $this->checkFacetIsNotActive('Child 4');
    $this->checkFacetIsActive('Parent 2');
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
   * Tests hierarchy breadcrumbs.
   */
  public function testHierarchyBreadcrumb() {
    $this->drupalGet('admin/config/search/facets');
    $this->clickLink('Configure', 1);
    $default_config = [
      'filter_key' => 'f',
      'url_processor' => 'query_string',
      'breadcrumb[active]' => TRUE,
      'breadcrumb[group]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $default_config, 'Save');

    $block = [
      'region' => 'footer',
      'label' => 'Breadcrumbs',
      'provider' => 'system',
    ];
    $this->drupalPlaceBlock('system_breadcrumb_block', $block);
    $this->resetAll();

    $edit = [
      'facet_settings[expand_hierarchy]' => '1',
      'facet_settings[use_hierarchy]' => '1',
      'facet_settings[translate_entity][status]' => '1',
      'facet_sorting[display_value_widget_order][status]' => '1',
      'facet_sorting[display_value_widget_order][settings][sort]' => 'ASC',
      'facet_sorting[count_widget_order][status]' => '0',
      'facet_sorting[active_widget_order][status]' => '0',
    ];
    $this->drupalPostForm($this->facetEditPage, $edit, 'Save');

    $initial_query = ['search_api_fulltext' => 'foo', 'test_param' => 1];
    $this->drupalGet('search-api-test-fulltext', ['query' => $initial_query]);
    $this->clickLink('Child 2');
    $this->checkFacetIsActive('Child 2');

    $this->assertSession()->pageTextContains('hierarchical facet: Parent 1');
    $this->clickLink('hierarchical facet: Parent 1');
    $this->checkFacetIsActive('Parent 1');
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
