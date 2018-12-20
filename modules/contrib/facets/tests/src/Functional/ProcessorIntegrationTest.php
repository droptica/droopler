<?php

namespace Drupal\Tests\facets\Functional;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Processor\SortProcessorInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Item\Field;

/**
 * Tests the processor functionality.
 *
 * @group facets
 */
class ProcessorIntegrationTest extends FacetsTestBase {

  /**
   * The url of the edit form.
   *
   * @var string
   */
  protected $editForm;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets_custom_widget',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    // Set up example content types and insert 10 new content items.
    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertEquals($this->indexItems($this->indexId), 5, '5 items were indexed.');
    $this->insertExampleContent();
    $this->assertEquals($this->indexItems($this->indexId), 5, '5 items were indexed.');
  }

  /**
   * Tests for the processors behavior in the backend.
   */
  public function testProcessorAdmin() {
    $facet_name = "Guanaco";
    $facet_id = "guanaco";

    $this->createFacet($facet_name, $facet_id);

    // Go to the processors form and check that the count limit processor is not
    // checked.
    $this->drupalGet('admin/config/search/facets/' . $facet_id . '/edit');
    $this->assertSession()->checkboxNotChecked('edit-facet-settings-count-limit-status');

    $form = ['facet_settings[count_limit][status]' => TRUE];
    $this->drupalPostForm(NULL, $form, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-facet-settings-count-limit-status');

    // Enable the sort processor and change sort direction, check that the
    // change is persisted.
    $form = [
      'facet_sorting[active_widget_order][status]' => TRUE,
      'facet_sorting[active_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm(NULL, $form, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-facet-sorting-active-widget-order-status');
    $this->assertSession()->checkboxChecked('edit-facet-sorting-active-widget-order-settings-sort-desc');

    // Add an extra processor so we can test the weights as well.
    $form = [
      'facet_settings[hide_non_narrowing_result_processor][status]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $form, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-facet-settings-count-limit-status');
    $this->assertSession()->checkboxChecked('edit-facet-settings-hide-non-narrowing-result-processor-status');
    $this->assertOptionSelected('edit-processors-count-limit-weights-build', 50);
    $this->assertOptionSelected('edit-processors-hide-non-narrowing-result-processor-weights-build', 40);

    // Change the weight of one of the processors and test that the weight
    // change persisted.
    $form = [
      'facet_settings[hide_non_narrowing_result_processor][status]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
      'processors[hide_non_narrowing_result_processor][weights][build]' => 5,
    ];
    $this->drupalPostForm(NULL, $form, 'Save');
    $this->assertSession()->checkboxChecked('edit-facet-settings-count-limit-status');
    $this->assertSession()->checkboxChecked('edit-facet-settings-hide-non-narrowing-result-processor-status');
    $this->assertOptionSelected('edit-processors-count-limit-weights-build', 50);
    $this->assertOptionSelected('edit-processors-hide-non-narrowing-result-processor-weights-build', 5);
  }

  /**
   * Tests the for processors in the frontend with a 'keywords' facet.
   */
  public function testProcessorIntegration() {
    $facet_name = "Vicuña";
    $facet_id = "vicuna";
    $this->editForm = 'admin/config/search/facets/' . $facet_id . '/edit';

    $this->createFacet($facet_name, $facet_id, 'keywords');
    $this->drupalPostForm($this->editForm, ['facet_settings[query_operator]' => 'and'], 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 10 search results');
    $this->assertSession()->pageTextContains('grape');
    $this->assertSession()->pageTextContains('orange');
    $this->assertSession()->pageTextContains('apple');
    $this->assertSession()->pageTextContains('strawberry');
    $this->assertSession()->pageTextContains('banana');

    $this->checkCountLimitProcessor();
    $this->checkExcludeItems();
    $this->checkHideNonNarrowingProcessor();
    $this->checkHideActiveItems();
  }

  /**
   * Tests the for processors in the frontend with a 'boolean' facet.
   */
  public function testBooleanProcessorIntegration() {
    $field_name = 'field_boolean';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'boolean',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'item',
    ]);
    $field->save();

    $index = $this->getIndex();

    // Index a boolean field.
    $boolean_field = new Field($index, $field_name);
    $boolean_field->setType('integer');
    $boolean_field->setPropertyPath($field_name);
    $boolean_field->setDatasourceId('entity:entity_test_mulrev_changed');
    $boolean_field->setLabel('BooleanField');
    $index->addField($boolean_field);

    $index->save();
    $this->indexItems($this->indexId);

    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');
    $entity_test_storage->create([
      'name' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange'],
      'category' => 'item_category',
      $field_name => TRUE,
    ])->save();
    $entity_test_storage->create([
      'name' => 'quux quuux',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['apple'],
      'category' => 'item_category',
      $field_name => FALSE,
    ])->save();

    $this->indexItems($this->indexId);

    $facet_name = "Boolean";
    $facet_id = "boolean";

    // Create facet.
    $this->editForm = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet($facet_name, $facet_id, $field_name);

    // Check values.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('1');
    $this->assertFacetLabel('0');

    $form = [
      'facet_settings[boolean_item][status]' => TRUE,
      'facet_settings[boolean_item][settings][on_value]' => 'Yes',
      'facet_settings[boolean_item][settings][off_value]' => 'No',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-facet-settings-boolean-item-status');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('Yes');
    $this->assertFacetLabel('No');

    $form = [
      'facet_settings[boolean_item][status]' => TRUE,
      'facet_settings[boolean_item][settings][on_value]' => 'Øn',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('Øn');
    $this->assertEmpty($this->findFacetLink('1'));
    $this->assertEmpty($this->findFacetLink('0'));

    $form = [
      'facet_settings[boolean_item][status]' => TRUE,
      'facet_settings[boolean_item][settings][off_value]' => 'Øff',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('Øff');
    $this->assertEmpty($this->findFacetLink('1'));
    $this->assertEmpty($this->findFacetLink('0'));
  }

  /**
   * Tests the for configuration of granularity processor.
   */
  public function testNumericGranularity() {
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
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    $index = $this->getIndex();

    // Index the taxonomy and entity reference fields.
    $integerField = new Field($index, $field_name);
    $integerField->setType('integer');
    $integerField->setPropertyPath($field_name);
    $integerField->setDatasourceId('entity:entity_test_mulrev_changed');
    $integerField->setLabel('IntegerField');
    $index->addField($integerField);

    $index->save();
    $this->indexItems($this->indexId);

    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');

    foreach ([30, 35, 40, 100] as $val) {
      $entity_test_storage->create([
        'name' => 'foo bar baz',
        'body' => 'test test int',
        'type' => 'item',
        'keywords' => ['orange'],
        'category' => 'item_category',
        $field_name => $val,
      ])->save();
    }

    $this->indexItems($this->indexId);

    $facet_id = "integer";

    // Create facet.
    $this->editForm = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet("Integer", $facet_id, $field_name);

    // Check values.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('100');
    $this->assertFacetLabel('30');
    $this->assertFacetLabel('35');
    $this->assertFacetLabel('40');

    $form = [
      'facet_settings[granularity_item][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    // Check values.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('30 - 31 (1)');
    $this->assertFacetLabel('35 - 36');
    $this->assertFacetLabel('40 - 41');
    $this->assertFacetLabel('100 - 101');

    $form = [
      'facet_settings[granularity_item][status]' => TRUE,
      'facet_settings[granularity_item][settings][granularity]' => 10,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    // Check values.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertFacetLabel('30 - 40 (2)');
    $this->assertEmpty($this->findFacetLink('35 - 36'));
    $this->assertFacetLabel('40 - 50');
    $this->assertFacetLabel('100 - 110');
  }

  /**
   * Tests the for sorting processors in the frontend with a 'keywords' facet.
   */
  public function testSortingWidgets() {
    $facet_name = "Huacaya alpaca";
    $facet_id = "huacaya_alpaca";
    $this->editForm = 'admin/config/search/facets/' . $facet_id . '/edit';

    $this->createFacet($facet_name, $facet_id, 'keywords');

    $this->checkSortByActive();
    $this->checkSortByCount();
    $this->checkSortByDisplay();
    $this->checkSortByRaw();
  }

  /**
   * Tests sorting of results.
   */
  public function testResultSorting() {
    $id = 'burrowing_owl';
    $name = 'Burrowing owl';
    $this->editForm = 'admin/config/search/facets/' . $id . '/edit';

    $this->createFacet($name, $id, 'keywords');
    $this->disableAllFacetSorts();

    $values = [
      'facet_sorting[display_value_widget_order][status]' => TRUE,
      'widget_config[show_numbers]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $values, 'Save');

    $expected_results = [
      'apple',
      'banana',
      'grape',
      'orange',
      'strawberry',
    ];

    $this->drupalGet('search-api-test-fulltext');
    foreach ($expected_results as $k => $link) {
      if ($k > 0) {
        $x = $expected_results[($k - 1)];
        $y = $expected_results[$k];
        $this->assertStringPosition($x, $y);
      }
    }

    // Sort by count, then by display value.
    $values['facet_sorting[count_widget_order][status]'] = TRUE;
    $values['facet_sorting[count_widget_order][settings][sort]'] = 'ASC';
    $values['processors[count_widget_order][weights][sort]'] = 1;
    $values['facet_sorting[display_value_widget_order][status]'] = TRUE;
    $values['processors[display_value_widget_order][weights][sort]'] = 2;
    $this->disableAllFacetSorts();
    $this->drupalPostForm($this->editForm, $values, 'Save');

    $expected_results = [
      'banana',
      'apple',
      'strawberry',
      'grape',
      'orange',
    ];

    $this->drupalGet('search-api-test-fulltext');
    foreach ($expected_results as $k => $link) {
      if ($k > 0) {
        $x = $expected_results[($k - 1)];
        $y = $expected_results[$k];
        $this->assertStringPosition($x, $y);
      }
    }

    $values['facet_sorting[display_value_widget_order][status]'] = TRUE;
    $values['facet_sorting[count_widget_order][status]'] = TRUE;
    $values['facet_sorting[count_widget_order][settings][sort]'] = 'ASC';
    $this->drupalPostForm($this->editForm, $values, 'Save');
    $this->assertSession()->checkboxChecked('edit-facet-sorting-display-value-widget-order-status');
    $this->assertSession()->checkboxChecked('edit-facet-sorting-count-widget-order-status');

    $expected_results = [
      'banana',
      'apple',
      'strawberry',
      'grape',
      'orange',
    ];

    $this->drupalGet('search-api-test-fulltext');
    foreach ($expected_results as $k => $link) {
      if ($k > 0) {
        $x = $expected_results[($k - 1)];
        $y = $expected_results[$k];
        $this->assertStringPosition($x, $y);
      }
    }
  }

  /**
   * Tests the count limit processor.
   */
  protected function checkCountLimitProcessor() {
    $this->drupalGet($this->editForm);

    $form = [
      'widget_config[show_numbers]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-facet-settings-count-limit-status');
    $form = [
      'widget_config[show_numbers]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $form = [
      'widget_config[show_numbers]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
      'facet_settings[count_limit][settings][minimum_items]' => 5,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 10 search results');
    $this->assertFacetLabel('grape (6)');
    $this->assertSession()->pageTextNotContains('apple');

    $form = [
      'widget_config[show_numbers]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
      'facet_settings[count_limit][settings][minimum_items]' => 1,
      'facet_settings[count_limit][settings][maximum_items]' => 5,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 10 search results');
    $this->assertSession()->pageTextNotContains('grape');
    $this->assertFacetLabel('apple (4)');

    $form = [
      'widget_config[show_numbers]' => FALSE,
      'facet_settings[count_limit][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');
  }

  /**
   * Tests the exclude items.
   */
  protected function checkExcludeItems() {
    $form = [
      'facet_settings[exclude_specified_items][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $form = [
      'facet_settings[exclude_specified_items][status]' => TRUE,
      'facet_settings[exclude_specified_items][settings][exclude]' => 'banana',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 10 search results');
    $this->assertSession()->pageTextContains('grape');
    $this->assertSession()->pageTextNotContains('banana');

    $form = [
      'facet_settings[exclude_specified_items][status]' => TRUE,
      'facet_settings[exclude_specified_items][settings][exclude]' => '(.*)berry',
      'facet_settings[exclude_specified_items][settings][regex]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 10 search results');
    $this->assertSession()->pageTextNotContains('strawberry');
    $this->assertSession()->pageTextContains('grape');

    $form = [
      'facet_settings[exclude_specified_items][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');
  }

  /**
   * Tests hiding non-narrowing results.
   */
  protected function checkHideNonNarrowingProcessor() {
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 10 search results');
    $this->assertFacetLabel('apple');

    $this->clickLink('apple');
    $this->assertSession()->pageTextContains('Displaying 4 search results');
    $this->assertFacetLabel('grape');

    $form = [
      'facet_settings[hide_non_narrowing_result_processor][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 10 search results');
    $this->assertFacetLabel('apple');

    $this->clickLink('apple');
    $this->assertSession()->pageTextContains('Displaying 4 search results');
    $this->assertSession()->linkNotExists('grape');

    $form = [
      'facet_settings[hide_non_narrowing_result_processor][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');
  }

  /**
   * Tests hiding active results.
   */
  protected function checkHideActiveItems() {
    $form = [
      'facet_settings[hide_active_items_processor][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 10 search results');
    $this->assertFacetLabel('grape');
    $this->assertFacetLabel('banana');

    $this->clickLink('grape');
    $this->assertSession()->pageTextContains('Displaying 6 search results');
    $this->assertSession()->linkNotExists('grape');
    $this->assertFacetLabel('banana');

    $form = [
      'facet_settings[hide_active_items_processor][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');
  }

  /**
   * Tests the active widget order.
   */
  protected function checkSortByActive() {
    $this->disableAllFacetSorts();
    $form = [
      'facet_sorting[active_widget_order][status]' => TRUE,
      'facet_sorting[active_widget_order][settings][sort]' => 'ASC',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->clickLink('strawberry');
    $this->assertStringPosition('strawberry', 'grape');

    $form = [
      'facet_sorting[active_widget_order][status]' => TRUE,
      'facet_sorting[active_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->clickLink('strawberry');
    $this->assertStringPosition('grape', 'strawberry');

    $form = [
      'facet_sorting[active_widget_order][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');
  }

  /**
   * Tests the active widget order.
   */
  protected function checkSortByCount() {
    $this->disableAllFacetSorts();
    $form = [
      'widget_config[show_numbers]' => TRUE,
      'facet_sorting[count_widget_order][status]' => TRUE,
      'facet_sorting[count_widget_order][settings][sort]' => 'ASC',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('banana', 'apple');
    $this->assertStringPosition('banana', 'strawberry');
    $this->assertStringPosition('apple', 'orange');

    $form = [
      'facet_sorting[count_widget_order][status]' => TRUE,
      'facet_sorting[count_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('apple', 'banana');
    $this->assertStringPosition('strawberry', 'banana');
    $this->assertStringPosition('orange', 'apple');

    $form = [
      'widget_config[show_numbers]' => FALSE,
      'facet_sorting[count_widget_order][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');
  }

  /**
   * Tests the display order.
   */
  protected function checkSortByDisplay() {
    $this->disableAllFacetSorts();
    $form = ['facet_sorting[display_value_widget_order][status]' => TRUE];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('grape', 'strawberry');
    $this->assertStringPosition('apple', 'banana');

    $form = [
      'facet_sorting[display_value_widget_order][status]' => TRUE,
      'facet_sorting[display_value_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('strawberry', 'grape');
    $this->assertStringPosition('banana', 'apple');

    $form = ['facet_sorting[display_value_widget_order][status]' => FALSE];
    $this->drupalPostForm($this->editForm, $form, 'Save');
  }

  /**
   * Tests the display order.
   */
  protected function checkSortByRaw() {
    $this->disableAllFacetSorts();
    $form = [
      'facet_sorting[raw_value_widget_order][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('grape', 'strawberry');
    $this->assertStringPosition('apple', 'banana');

    $form = [
      'facet_sorting[raw_value_widget_order][status]' => TRUE,
      'facet_sorting[raw_value_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('strawberry', 'grape');
    $this->assertStringPosition('banana', 'apple');

    $form = [
      'facet_sorting[raw_value_widget_order][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');
  }

  /**
   * Disables all sorting processors for a clean testing base.
   */
  protected function disableAllFacetSorts($path = FALSE) {
    $settings = [
      'facet_sorting[raw_value_widget_order][status]' => FALSE,
      'facet_sorting[display_value_widget_order][status]' => FALSE,
      'facet_sorting[count_widget_order][status]' => FALSE,
      'facet_sorting[active_widget_order][status]' => FALSE,
    ];
    if (!$path) {
      $path = $this->editForm;
    }
    $this->drupalPostForm($path, $settings, 'Save');
  }

  /**
   * Checks if the list processor changes machine name to the display label.
   */
  public function testListProcessor() {
    entity_test_create_bundle('basic', "Basic page", 'entity_test_mulrev_changed');
    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');

    // Add an entity with basic page content type.
    $entity_test_storage->create([
      'name' => 'AC0871108',
      'body' => 'Eamus Catuli',
      'type' => 'basic',
    ])->save();
    $this->indexItems($this->indexId);

    $facet_name = "Eamus Catuli";
    $facet_id = "eamus_catuli";
    $editForm = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet($facet_name, $facet_id);

    // Go to the overview and check that the machine names are used as facets.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 11 search results');
    $this->assertFacetLabel('basic');

    // Edit the facet to use the list_item processor.
    $edit = [
      'facet_settings[list_item][status]' => TRUE,
    ];
    $this->drupalPostForm($editForm, $edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-facet-settings-list-item-status');

    // Go back to the overview and check that now the label is being used
    // instead.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 11 search results');
    $this->assertFacetLabel('Basic page');
  }

  /**
   * Test pre query processor.
   */
  public function testPreQueryProcessor() {
    $facet_name = "Eamus Catuli";
    $facet_id = "eamus_catuli";
    $editForm = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet($facet_name, $facet_id);

    $edit = [
      'facet_settings[test_pre_query][status]' => TRUE,
      'facet_settings[test_pre_query][settings][test_value]' => 'Llama',
    ];
    $this->drupalPostForm($editForm, $edit, 'Save');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Llama');
  }

  /**
   * Tests the facet support for a widget.
   */
  public function testSupportsFacet() {
    $id = 'masked_owl';
    $this->createFacet('Australian masked owl', $id);

    // Go to the facet edit page and check to see if the custom processor shows
    // up.
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');
    $this->assertSession()->pageTextContains('test pre query');

    // Make the ::supportsFacet method on the custom processor return false.
    \Drupal::state()->set('facets_test_supports_facet', FALSE);

    // Go to the facet edit page and check to see if the custom processor is
    // now hidden.
    $this->drupalGet('admin/config/search/facets/' . $id . '/edit');
    $this->assertSession()->pageTextNotContains('test pre query');
  }

  /**
   * Test HideOnlyOneItemProcessor.
   *
   * Test if after clicking an item that has only one item, the facet block no
   * longer shows.
   */
  public function testHideOnlyOneItemProcessor() {
    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');

    // Load all items and delete them.
    $all = $entity_test_storage->loadMultiple();
    foreach ($all as $item) {
      $item->delete();
    }
    $entity_test_storage->create([
      'name' => 'baz baz',
      'body' => 'foo test',
      'type' => 'article',
      'keywords' => ['kiwi'],
      'category' => 'article_category',
    ])->save();
    $this->indexItems($this->indexId);

    $facet_name = 'Drupalcon Vienna';
    $facet_id = 'drupalcon_vienna';
    $this->editForm = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet($facet_name, $facet_id, 'keywords');

    $form = [
      'facet_settings[hide_1_result_facet][status]' => TRUE,
      'facet_settings[query_operator]' => 'and',
    ];
    $this->drupalPostForm($this->editForm, $form, 'Save');
    $this->drupalGet('search-api-test-fulltext');

    $this->assertSession()->pageTextContains('Displaying 1 search results');
    $this->assertNoFacetBlocksAppear();
  }

  /**
   * Tests that processors are hidden when the correct fields aren't there.
   */
  public function testHiddenProcessors() {
    $facet_id = 'alpaca';
    $this->editForm = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet('Alpaca', $facet_id);
    $this->drupalGet($this->editForm);
    $this->assertSession()->pageTextNotContains('Boolean item label');
    $this->assertSession()->pageTextNotContains('Transform UID to user name');
    $this->assertSession()->pageTextNotContains('Transform entity ID to label');
    $this->assertSession()->pageTextNotContains('Sort by taxonomy term weight');
  }

  /**
   * Tests the configuration of the processors.
   */
  public function testProcessorConfig() {
    $this->createFacet('Llama', 'llama');

    $facet_id = 'alpaca';
    $this->editForm = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet('Alpaca', $facet_id);
    $this->drupalGet($this->editForm);

    $facet = Facet::load($facet_id);

    /** @var \Drupal\facets\Processor\ProcessorInterface $processor */
    foreach ($facet->getProcessors(FALSE) as $processor) {
      // Sort processors have a different form key, so don't bother for now.
      if ($processor instanceof SortProcessorInterface) {
        continue;
      }
      // These processors are hidden by default, see also
      // ::testHiddenProcessors.
      $hiddenProcessors = [
        'boolean_item',
        'translate_entity',
        'uid_to_username_callback',
      ];
      if (in_array($processor->getPluginId(), $hiddenProcessors)) {
        continue;
      }

      $this->drupalPostForm(NULL, ["facet_settings[{$processor->getPluginId()}][status]" => '1'], 'Save');
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  /**
   * Tests the list item processor with underscores in the bundle.
   */
  public function testEntityTranslateWithUnderScores() {
    entity_test_create_bundle('test_with_underscore', "Test with underscore", 'entity_test_mulrev_changed');
    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');

    // Add an entity with basic page content type.
    $entity_test_storage->create([
      'name' => 'llama',
      'body' => 'llama.',
      'type' => 'test_with_underscore',
    ])->save();
    $this->indexItems($this->indexId);

    $facet_id = 'owl';
    $editForm = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet('Owl', $facet_id);

    // Go to the overview and check that the machine names are used as facets.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 11 search results');
    $this->assertFacetLabel('test_with_underscore');

    // Edit the facet to use the list_item processor.
    $edit = [
      'facet_settings[list_item][status]' => TRUE,
    ];
    $this->drupalPostForm($editForm, $edit, 'Save');

    // Go back to the overview and check that now the label is being used
    // instead.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->pageTextContains('Displaying 11 search results');
    $this->assertFacetLabel('Test with underscore');
  }

}
