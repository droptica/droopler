<?php

namespace Drupal\Tests\search_api\Kernel\Index;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Utility\Utility;

/**
 * Tests whether importing of index configuration works correctly.
 *
 * @group search_api
 */
class IndexImportTest extends KernelTestBase {

  /**
   * The search index storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $storage;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'field',
    'search_api',
    'search_api_db',
    'search_api_test_db',
    'search_api_test',
    'user',
    'system',
    'entity_test',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installSchema('system', ['router']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    $this->installConfig(['search_api_test_db']);

    $this->storage = $this->container->get('entity_type.manager')->getStorage('search_api_index');
  }

  /**
   * Tests processors are set after import.
   */
  public function testIndexImport() {
    // Check initial conditions.
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->storage->load('database_search_index');
    $this->assertFalse($index->isValidProcessor('stopwords'), 'Processor is not in index');

    // Prepare the import by creating a copy of the active config in sync.
    /** @var \Drupal\Core\Config\StorageInterface $sync */
    $sync = $this->container->get('config.storage.sync');
    /** @var \Drupal\Core\Config\StorageInterface $active */
    $active = $this->container->get('config.storage');
    $this->copyConfig($active, $sync);

    // Make changes to the configuration in "sync" so there actually is
    // something to import.
    $expected_stopwords = ['a', 'an', 'and', 'are', 'as'];
    $import_config = $sync->read('search_api.index.database_search_index');
    $import_config['processor_settings']['stopwords'] = [
      'weights' => [
        'preprocess_query' => -10,
        'postprocess_query' => -10,
      ],
      'fields' => [
        'name',
      ],
      'stopwords' => $expected_stopwords,
    ];
    $sync->write('search_api.index.database_search_index', $import_config);

    // The system.site key is required for import validation.
    $sync->write('system.site', []);

    // Import the test configuration.
    $config_importer = $this->configImporter();
    $this->assertTrue($config_importer->hasUnprocessedConfigurationChanges(), 'Import prepared');
    $config_importer->import();

    // Ensure the static cache is clear and check that our change was correctly
    // imported.
    $this->storage->resetCache(['database_search_index']);
    /** @var \Drupal\search_api\IndexInterface $imported_index */
    $imported_index = $this->storage->load('database_search_index');
    $this->assertTrue($imported_index->isValidProcessor('stopwords'), 'Processor is in index after import');

    // Check that the processor does not have the default configuration.
    $processor_config = $imported_index->getProcessor('stopwords')->getConfiguration();
    $this->assertArrayHasKey('stopwords', $processor_config, 'Stopwords are configured');
    $actual_stopwords = $processor_config['stopwords'];
    $this->assertEquals($expected_stopwords, $actual_stopwords, 'Processor config was correctly set during import');
  }

}
