<?php

namespace Drupal\Tests\search_api\Kernel\Datasource;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility\Utility;

/**
 * Tests translation handling of the content entity datasource.
 *
 * @group search_api
 */
class LanguageKernelTest extends KernelTestBase {

  /**
   * The test entity type used in the test.
   *
   * @var string
   */
  protected $testEntityTypeId = 'entity_test_mulrev_changed';

  /**
   * The search server used for testing.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The search index used for testing.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'search_api',
    'search_api_test',
    'language',
    'field',
    'user',
    'system',
    'entity_test',
  ];

  /**
   * An array of langcodes.
   *
   * @var string[]
   */
  protected $langcodes;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Enable translation for the entity_test module.
    \Drupal::state()->set('entity_test.translation', TRUE);

    $this->installSchema('search_api', ['search_api_item']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installConfig('search_api');

    // Create the default languages.
    $this->installConfig(['language']);
    $this->langcodes = [];
    for ($i = 0; $i < 3; ++$i) {
      /** @var \Drupal\language\Entity\ConfigurableLanguage $language */
      $language = ConfigurableLanguage::create([
        'id' => 'l' . $i,
        'label' => 'language - ' . $i,
        'weight' => $i,
      ]);
      $this->langcodes[$i] = $language->getId();
      $language->save();
    }

    // Create an entity reference field on the test entity type.
    FieldStorageConfig::create([
      'field_name' => 'link',
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'entity_reference',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'entity_test_mulrev_changed',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => 'link',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'entity_test_mulrev_changed',
      'label' => 'Link',
    ])->save();

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    // Create a test server.
    $this->server = Server::create([
      'name' => 'Test Server',
      'id' => 'test_server',
      'status' => 1,
      'backend' => 'search_api_test',
    ]);
    $this->server->save();

    // Create a test index.
    $this->index = Index::create([
      'name' => 'Test Index',
      'id' => 'test_index',
      'status' => 1,
      'datasource_settings' => [
        'entity:' . $this->testEntityTypeId => [],
      ],
      'tracker_settings' => [
        'default' => [],
      ],
      'field_settings' => [
        'link' => [
          'label' => 'Link name',
          'type' => 'string',
          'datasource_id' => 'entity:entity_test_mulrev_changed',
          'property_path' => 'link:entity:name',
        ],
      ],
      'server' => $this->server->id(),
      'options' => ['index_directly' => FALSE],
    ]);
    $this->index->save();
  }

  /**
   * Tests translation handling of the content entity datasource.
   */
  public function testItemTranslations() {
    // Test retrieving language and translations when no translations are
    // available.
    /** @var \Drupal\entity_test\Entity\EntityTestMulRevChanged $entity_1 */
    $entity_1 = EntityTestMulRevChanged::create([
      'id' => 1,
      'name' => 'test 1',
      'user_id' => $this->container->get('current_user')->id(),
    ]);
    $entity_1->save();
    $entity_1->set('link', $entity_1->id());
    $this->assertEquals('en', $entity_1->language()->getId(), new FormattableMarkup('%entity_type: Entity language set to site default.', ['%entity_type' => $this->testEntityTypeId]));
    $this->assertFalse($entity_1->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: No translations are available', ['%entity_type' => $this->testEntityTypeId]));

    /** @var \Drupal\entity_test\Entity\EntityTestMulRevChanged $entity_2 */
    $entity_2 = EntityTestMulRevChanged::create([
      'id' => 2,
      'name' => 'test 2',
      'user_id' => $this->container->get('current_user')->id(),
    ]);
    $entity_2->save();
    $this->assertEquals('en', $entity_2->language()->getId(), new FormattableMarkup('%entity_type: Entity language set to site default.', ['%entity_type' => $this->testEntityTypeId]));
    $this->assertFalse($entity_2->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: No translations are available', ['%entity_type' => $this->testEntityTypeId]));

    // Test that the datasource returns the correct item IDs.
    $datasource = $this->index->getDatasource('entity:' . $this->testEntityTypeId);
    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = [
      '1:en',
      '2:en',
    ];
    $this->assertEquals($expected, $datasource_item_ids, 'Datasource returns correct item ids.');

    // Test indexing the new entity.
    $this->assertEquals(0, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'The index is empty.');
    $this->assertEquals(2, $this->index->getTrackerInstance()->getTotalItemsCount(), 'There are two items to be indexed.');
    $this->index->indexItems();
    $this->assertEquals(2, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'Two items have been indexed.');

    // Now, make the first entity language-specific by assigning a language.
    $default_langcode = $this->langcodes[0];
    $entity_1->get('langcode')->setValue($default_langcode);
    $entity_1->save();
    $this->assertEquals(\Drupal::languageManager()->getLanguage($this->langcodes[0]), $entity_1->language(), new FormattableMarkup('%entity_type: Entity language retrieved.', ['%entity_type' => $this->testEntityTypeId]));
    $this->assertFalse($entity_1->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: No translations are available', ['%entity_type' => $this->testEntityTypeId]));

    // Test that the datasource returns the correct item IDs.
    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = [
      '1:' . $this->langcodes[0],
      '2:en',
    ];
    $this->assertEquals($expected, $datasource_item_ids, 'Datasource returns correct item ids.');

    // Test that the index needs to be updated.
    $this->assertEquals(1, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'The updated item needs to be reindexed.');
    $this->assertEquals(2, $this->index->getTrackerInstance()->getTotalItemsCount(), 'There are two items in total.');

    // Set two translations for the first entity and test that the datasource
    // returns three separate item IDs, one for each translation.
    $translation = $entity_1->addTranslation($this->langcodes[1]);
    $this->assertEquals(1, $entity_1->link[0]->entity->id());
    $translation->set('name', 'test 1 - ' . $this->langcodes[1]);
    $translation->set('link', $entity_1->id());
    $translation->save();
    $translation = $entity_1->addTranslation($this->langcodes[2]);
    $translation->set('name', 'test 1 - ' . $this->langcodes[2]);
    $translation->set('link', $entity_1->id());
    $translation->save();
    $this->assertTrue($entity_1->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: Translations are available', ['%entity_type' => $this->testEntityTypeId]));

    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = [
      '1:' . $this->langcodes[0],
      '1:' . $this->langcodes[1],
      '1:' . $this->langcodes[2],
      '2:en',
    ];
    $this->assertEquals($expected, $datasource_item_ids, 'Datasource returns correct item ids for a translated entity.');

    foreach ($datasource->loadMultiple($datasource_item_ids) as $id => $object) {
      // Test whether the item reports the correct language.
      list($entity_id, $langcode) = explode(':', $id, 2);
      $item = \Drupal::getContainer()
        ->get('search_api.fields_helper')
        ->createItemFromObject($this->index, $object, NULL, $datasource);
      $this->assertEquals($langcode, $item->getLanguage(), "Item with ID '$id' has the correct language set.");

      // Test whether nested field extraction works correctly.
      if ($entity_id == 1) {
        $field = $item->getField('link');
        $translation_label = $entity_1->getTranslation($langcode)->label();
        $this->assertEquals([$translation_label], $field->getValues());
      }
    }

    // Tests that a query with an empty array of languages will return an empty
    // result set, without going through the server. (Our test backend wouldn't
    // care about languages.)
    $results = $this->index->query()->setLanguages([])->execute();
    $this->assertEquals(0, $results->getResultCount(), 'Query with empty languages list returned correct number of results.');
    $this->assertEquals([], $results->getResultItems(), 'Query with empty languages list returned correct result.');

    // Test that the index needs to be updated.
    $this->assertEquals(1, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'The updated items needs to be reindexed.');
    $this->assertEquals(4, $this->index->getTrackerInstance()->getTotalItemsCount(), 'There are four items in total.');

    // Delete one translation and test that the datasource returns only three
    // items.
    $entity_1->removeTranslation($this->langcodes[2]);
    $entity_1->save();

    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = [
      '1:' . $this->langcodes[0],
      '1:' . $this->langcodes[1],
      '2:en',
    ];
    $this->assertEquals($expected, $datasource_item_ids, 'Datasource returns correct item ids for a translated entity.');

    // Test reindexing.
    $this->assertEquals(3, $this->index->getTrackerInstance()->getTotalItemsCount(), 'There are three items in total.');
    $this->assertEquals(1, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'The updated items needs to be reindexed.');
    $this->index->indexItems();
    $this->assertEquals(3, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'Three items are indexed.');
  }

  /**
   * Tests tracking when the datasource has only specific languages enabled.
   */
  public function testDatasourceLanguagesSetting() {
    $datasource_id = 'entity:' . $this->testEntityTypeId;
    $datasource = $this->index->getDatasource($datasource_id);
    $datasource->setConfiguration([
      'languages' => [
        'default' => FALSE,
        'selected' => [$this->langcodes[0], $this->langcodes[2]],
      ],
    ]);
    $this->index->save();

    // Create new entities with our three custom languages and one with the
    // default language (English).
    /** @var \Drupal\entity_test\Entity\EntityTestMulRevChanged[] $entities */
    $entities = [];
    $uid = $this->container->get('current_user')->id();
    foreach ([0, 1, 2, 3] as $i) {
      $entity = EntityTestMulRevChanged::create([
        'id' => $i + 1,
        'name' => 'test 1',
        'user_id' => $uid,
      ]);
      if (!empty($this->langcodes[$i])) {
        $entity->set('langcode', $this->langcodes[$i]);
      }
      $entity->save();
      $entities[$i] = $entity;
    }

    // Only the ones with languages "l0" and "l2" should have been tracked.
    $tracker = $this->index->getTrackerInstance();
    $this->assertEquals(2, $tracker->getTotalItemsCount());
    $expected = [
      Utility::createCombinedId($datasource_id, "1:{$this->langcodes[0]}"),
      Utility::createCombinedId($datasource_id, "3:{$this->langcodes[2]}"),
    ];
    $actual = $tracker->getRemainingItems();
    sort($actual);
    $this->assertEquals($expected, $actual);

    // Add a translation for a disabled language. It should not be tracked.
    $entities[0]->addTranslation($this->langcodes[1])->save();
    $this->assertEquals(2, $tracker->getTotalItemsCount());
    $actual = $tracker->getRemainingItems();
    sort($actual);
    $this->assertEquals($expected, $actual);

    // Add a translation for an enabled language. It should be tracked.
    $langcode = $this->langcodes[0];
    $entities[1]->addTranslation($langcode)->save();
    $this->assertEquals(3, $tracker->getTotalItemsCount());
    $expected_1 = $expected;
    $expected_1[] = Utility::createCombinedId($datasource_id, "2:$langcode");
    sort($expected_1);
    $actual = $tracker->getRemainingItems();
    sort($actual);
    $this->assertEquals($expected_1, $actual);

    // Remove that translation again and verify that it's also deleted from the
    // tracker.
    $entities[1]->removeTranslation($langcode);
    $entities[1]->save();
    $this->assertEquals(2, $tracker->getTotalItemsCount());
    $actual = $tracker->getRemainingItems();
    sort($actual);
    $this->assertEquals($expected, $actual);
  }

}
