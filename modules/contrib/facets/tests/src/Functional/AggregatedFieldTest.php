<?php

namespace Drupal\Tests\facets\Functional;

use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Plugin\search_api\processor\Property\AggregatedFieldProperty;
use Drupal\search_api\Utility\Utility;
use Drupal\user\Entity\User;

/**
 * Class AggregatedFieldTest.
 *
 * @group facets
 */
class AggregatedFieldTest extends FacetsTestBase {

  /**
   * Users created for this test.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $users;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    $this->setUpExampleStructure();
    $this->insertExampleContent();

    foreach ([7 => 'Owl', 8 => 'Robin', 9 => 'Hawk'] as $i => $value) {
      $this->users[$i] = User::create([
        'uid' => $i,
        'name' => "User $value",
      ]);
      $this->users[$i]->save();

      $this->entities[$i] = EntityTestMulRevChanged::create([
        'id' => $i,
        'user_id' => $i,
        'name' => "Test entity $value name",
        'body' => "Test entity $value body",
      ]);
      $this->entities[$i]->save();
    }

    $plugin_creation_helper = \Drupal::getContainer()->get('search_api.plugin_helper');
    $fields_helper = \Drupal::getContainer()->get('search_api.fields_helper');

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load($this->indexId);

    // Add the user as a datasource.
    $index->addDatasource($plugin_creation_helper->createDatasourcePlugin($index, 'entity:user'));

    // Create the aggregated field property.
    $property = AggregatedFieldProperty::create('string');

    // Add and configure the aggregated field.
    $field = $fields_helper->createFieldFromProperty($index, $property, NULL, 'aggregated_field', 'aggregated_field', 'string');
    $field->setLabel('Aggregated field');
    $field->setConfiguration([
      'type' => 'union',
      'fields' => [
        Utility::createCombinedId('entity:entity_test_mulrev_changed', 'name'),
        Utility::createCombinedId('entity:user', 'name'),
      ],
    ]);
    $index->addField($field);
    $index->save();

    // Index all items, users and content.
    $this->assertEquals(16, $this->indexItems($this->indexId));
  }

  /**
   * Tests aggregated fields.
   *
   * @see https://www.drupal.org/node/2917323
   */
  public function testAggregatedField() {
    $facet_id = 'test_agg';

    // Go to the Add facet page and make sure that returns a 200.
    $facet_add_page = '/admin/config/search/facets/add-facet';
    $this->drupalGet($facet_add_page);
    $this->assertSession()->statusCodeEquals(200);

    $form_values = [
      'name' => 'Test agg',
      'id' => $facet_id,
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
      'facet_source_configs[search_api:views_page__search_api_test_view__page_1][field_identifier]' => 'aggregated_field',
    ];

    // Try filling out the form, and configure it to use the aggregated field.
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api:views_page__search_api_test_view__page_1'], 'Configure facet source');
    $this->drupalPostForm(NULL, $form_values, 'Save');

    // Check that nothing breaks.
    $this->assertSession()->statusCodeEquals(200);
  }

}
