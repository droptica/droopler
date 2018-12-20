<?php

namespace Drupal\Tests\search_api_db\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database as CoreDatabase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\search_api\data_type\value\TextToken;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_db\DatabaseCompatibility\GenericDatabase;
use Drupal\search_api_db\Plugin\search_api\backend\Database;
use Drupal\search_api_db\Tests\DatabaseTestsTrait;
use Drupal\Tests\search_api\Kernel\BackendTestBase;

/**
 * Tests index and search capabilities using the Database search backend.
 *
 * @see \Drupal\search_api_db\Plugin\search_api\backend\Database
 *
 * @group search_api
 */
class BackendTest extends BackendTestBase {

  use DatabaseTestsTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api_db',
    'search_api_test_db',
  ];

  /**
   * {@inheritdoc}
   */
  protected $serverId = 'database_search_server';

  /**
   * {@inheritdoc}
   */
  protected $indexId = 'database_search_index';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a dummy table that will cause a naming conflict with the backend's
    // default table names, thus testing whether it correctly reacts to such
    // conflicts.
    \Drupal::database()->schema()->createTable('search_api_db_database_search_index', [
      'fields' => [
        'id' => [
          'type' => 'int',
        ],
      ],
    ]);

    $this->installConfig(['search_api_test_db']);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkBackendSpecificFeatures() {
    $this->checkMultiValuedInfo();
    $this->editServerPartial();
    $this->searchSuccessPartial();
    $this->editServerStartsWith();
    $this->searchSuccessStartsWith();
    $this->editServerMinChars();
    $this->searchSuccessMinChars();
    $this->checkUnknownOperator();
    $this->checkDbQueryAlter();
    $this->checkFieldIdChanges();
  }

  /**
   * {@inheritdoc}
   */
  protected function backendSpecificRegressionTests() {
    $this->regressionTest2557291();
    $this->regressionTest2511860();
    $this->regressionTest2846932();
    $this->regressionTest2926733();
    $this->regressionTest2938646();
    $this->regressionTest2925464();
    $this->regressionTest2994022();
  }

  /**
   * Tests that all tables and all columns have been created.
   */
  protected function checkServerBackend() {
    $db_info = $this->getIndexDbInfo();
    $normalized_storage_table = $db_info['index_table'];
    $field_infos = $db_info['field_tables'];

    $expected_fields = [
      'body',
      'category',
      'created',
      'id',
      'keywords',
      'name',
      'search_api_datasource',
      'search_api_language',
      'type',
      'width',
    ];
    $actual_fields = array_keys($field_infos);
    sort($actual_fields);
    $this->assertEquals($expected_fields, $actual_fields, 'All expected field tables were created.');

    $this->assertTrue(\Drupal::database()->schema()->tableExists($normalized_storage_table), 'Normalized storage table exists.');
    $this->assertHasPrimaryKey($normalized_storage_table, 'Normalized storage table has a primary key.');
    foreach ($field_infos as $field_id => $field_info) {
      if ($field_id != 'search_api_id') {
        $this->assertTrue(\Drupal::database()
          ->schema()
          ->tableExists($field_info['table']));
      }
      else {
        $this->assertEmpty($field_info['table']);
      }
      $this->assertTrue(\Drupal::database()->schema()->fieldExists($normalized_storage_table, $field_info['column']), new FormattableMarkup('Field column %column exists', ['%column' => $field_info['column']]));
    }
  }

  /**
   * Checks whether changes to the index's fields are picked up by the server.
   */
  protected function updateIndex() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex();

    // Remove a field from the index and check if the change is matched in the
    // server configuration.
    $field = $index->getField('keywords');
    if (!$field) {
      throw new \Exception();
    }
    $index->removeField('keywords');
    $index->save();

    $index_fields = array_keys($index->getFields());
    // Include the three "magic" fields we're indexing with the DB backend.
    $index_fields[] = 'search_api_datasource';
    $index_fields[] = 'search_api_language';

    $db_info = $this->getIndexDbInfo();
    $server_fields = array_keys($db_info['field_tables']);

    sort($index_fields);
    sort($server_fields);
    $this->assertEquals($index_fields, $server_fields);

    // Add the field back for the next assertions.
    $index->addField($field)->save();
  }

  /**
   * Verifies that the generated table names are correct.
   */
  protected function checkTableNames() {
    $this->assertEquals('search_api_db_database_search_index_1', $this->getIndexDbInfo()['index_table']);
    $this->assertEquals('search_api_db_database_search_index_text', $this->getIndexDbInfo()['field_tables']['body']['table']);
  }

  /**
   * Verifies that the stored information about multi-valued fields is correct.
   */
  protected function checkMultiValuedInfo() {
    $db_info = $this->getIndexDbInfo();
    $field_info = $db_info['field_tables'];

    $fields = [
      'name',
      'body',
      'type',
      'keywords',
      'category',
      'width',
      'search_api_datasource',
      'search_api_language',
    ];
    $multi_valued = [
      'name',
      'body',
      'keywords',
    ];
    foreach ($fields as $field_id) {
      $this->assertArrayHasKey($field_id, $field_info, "Field info saved for field $field_id.");
      if (in_array($field_id, $multi_valued)) {
        $this->assertFalse(empty($field_info[$field_id]['multi-valued']), "Field $field_id is stored as multi-value.");
      }
      else {
        $this->assertTrue(empty($field_info[$field_id]['multi-valued']), "Field $field_id is not stored as multi-value.");
      }
    }
  }

  /**
   * Edits the server to enable partial matches.
   */
  protected function editServerPartial() {
    $server = $this->getServer();
    $backend_config = $server->getBackendConfig();
    $backend_config['matching'] = 'partial';
    $server->setBackendConfig($backend_config);
    $this->assertTrue((bool) $server->save(), 'The server was successfully edited.');
    $this->resetEntityCache();
  }

  /**
   * Tests whether partial searches work.
   */
  protected function searchSuccessPartial() {
    $results = $this->buildSearch('foobaz')->range(0, 1)->execute();
    $this->assertResults([1], $results, 'Partial search for »foobaz«');

    $results = $this->buildSearch('foo', [], [], FALSE)
      ->sort('search_api_relevance', QueryInterface::SORT_DESC)
      ->sort('id')
      ->execute();
    $this->assertResults([1, 2, 4, 3, 5], $results, 'Partial search for »foo«');

    $results = $this->buildSearch('foo tes')->execute();
    $this->assertResults([1, 2, 3, 4], $results, 'Partial search for »foo tes«');

    $results = $this->buildSearch('oob est')->execute();
    $this->assertResults([1, 2, 3], $results, 'Partial search for »oob est«');

    $results = $this->buildSearch('foo nonexistent')->execute();
    $this->assertResults([], $results, 'Partial search for »foo nonexistent«');

    $results = $this->buildSearch('bar nonexistent')->execute();
    $this->assertResults([], $results, 'Partial search for »bar nonexistent«');

    $keys = [
      '#conjunction' => 'AND',
      'oob',
      [
        '#conjunction' => 'OR',
        'est',
        'nonexistent',
      ],
    ];
    $results = $this->buildSearch($keys)->execute();
    $this->assertResults([1, 2, 3], $results, 'Partial search for complex keys');

    $results = $this->buildSearch('foo', ['category,item_category'], [], FALSE)
      ->sort('id', QueryInterface::SORT_DESC)
      ->execute();
    $this->assertResults([2, 1], $results, 'Partial search for »foo« with additional filter');

    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('name', 'test');
    $conditions->addCondition('body', 'test');
    $query->addConditionGroup($conditions);
    $results = $query->execute();
    $this->assertResults([1, 2, 3, 4], $results, 'Partial search with multi-field fulltext filter');
  }

  /**
   * Edits the server to enable prefix matching.
   */
  protected function editServerStartsWith() {
    $server = $this->getServer();
    $backend_config = $server->getBackendConfig();
    $backend_config['matching'] = 'prefix';
    $server->setBackendConfig($backend_config);
    $this->assertTrue((bool) $server->save(), 'The server was successfully edited.');
    $this->resetEntityCache();
  }

  /**
   * Tests whether prefix matching works.
   */
  protected function searchSuccessStartsWith() {
    $results = $this->buildSearch('foobaz')->range(0, 1)->execute();
    $this->assertResults([1], $results, 'Prefix search for »foobaz«');

    $results = $this->buildSearch('foo', [], [], FALSE)
      ->sort('search_api_relevance', QueryInterface::SORT_DESC)
      ->sort('id')
      ->execute();
    $this->assertResults([1, 2, 4, 3, 5], $results, 'Prefix search for »foo«');

    $results = $this->buildSearch('foo tes')->execute();
    $this->assertResults([1, 2, 3, 4], $results, 'Prefix search for »foo tes«');

    $results = $this->buildSearch('oob est')->execute();
    $this->assertResults([], $results, 'Prefix search for »oob est«');

    $results = $this->buildSearch('foo nonexistent')->execute();
    $this->assertResults([], $results, 'Prefix search for »foo nonexistent«');

    $results = $this->buildSearch('bar nonexistent')->execute();
    $this->assertResults([], $results, 'Prefix search for »bar nonexistent«');

    $keys = [
      '#conjunction' => 'AND',
      'foob',
      [
        '#conjunction' => 'OR',
        'tes',
        'nonexistent',
      ],
    ];
    $results = $this->buildSearch($keys)->execute();
    $this->assertResults([1, 2, 3], $results, 'Prefix search for complex keys');

    $results = $this->buildSearch('foo', ['category,item_category'], [], FALSE)
      ->sort('id', QueryInterface::SORT_DESC)
      ->execute();
    $this->assertResults([2, 1], $results, 'Prefix search for »foo« with additional filter');

    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('name', 'test');
    $conditions->addCondition('body', 'test');
    $query->addConditionGroup($conditions);
    $results = $query->execute();
    $this->assertResults([1, 2, 3, 4], $results, 'Prefix search with multi-field fulltext filter');
  }

  /**
   * Edits the server to change the "Minimum word length" setting.
   */
  protected function editServerMinChars() {
    $server = $this->getServer();
    $backend_config = $server->getBackendConfig();
    $backend_config['min_chars'] = 4;
    $backend_config['matching'] = 'words';
    $server->setBackendConfig($backend_config);
    $success = (bool) $server->save();
    $this->assertTrue($success, 'The server was successfully edited.');

    $this->clearIndex();
    $this->indexItems($this->indexId);

    $this->resetEntityCache();
  }

  /**
   * Tests the results of some test searches with minimum word length of 4.
   */
  protected function searchSuccessMinChars() {
    $results = $this->getIndex()->query()->keys('test')->range(1, 2)->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Search for »test« returned correct number of results.');
    $this->assertEquals($this->getItemIds([4, 1]), array_keys($results->getResultItems()), 'Search for »test« returned correct result.');
    $this->assertEmpty($results->getIgnoredSearchKeys());
    $this->assertEmpty($results->getWarnings());

    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('name', 'test');
    $conditions->addCondition('body', 'test');
    $query->addConditionGroup($conditions);
    $results = $query->execute();
    $this->assertResults([1, 2, 3, 4], $results, 'Search with multi-field fulltext filter');

    $results = $this->buildSearch(NULL, ['body,test foobar'])->execute();
    $this->assertResults([3], $results, 'Search with multi-term fulltext filter');

    $results = $this->getIndex()->query()->keys('test foo')->execute();
    $this->assertResults([2, 4, 1, 3], $results, 'Search for »test foo«', ['foo']);

    $results = $this->buildSearch('foo', ['type,item'])->execute();
    $this->assertResults([1, 2, 3], $results, 'Search for »foo«', ['foo'], ['No valid search keys were present in the query.']);

    $keys = [
      '#conjunction' => 'AND',
      'test',
      [
        '#conjunction' => 'OR',
        'baz',
        'foobar',
      ],
      [
        '#conjunction' => 'OR',
        '#negation' => TRUE,
        'bar',
        'fooblob',
      ],
    ];
    $results = $this->buildSearch($keys)->execute();
    $this->assertResults([3], $results, 'Complex search 1', ['baz', 'bar']);

    $keys = [
      '#conjunction' => 'AND',
      'test',
      [
        '#conjunction' => 'OR',
        'baz',
        'foobar',
      ],
      [
        '#conjunction' => 'OR',
        '#negation' => TRUE,
        'bar',
        'fooblob',
      ],
    ];
    $results = $this->buildSearch($keys)->execute();
    $this->assertResults([3], $results, 'Complex search 2', ['baz', 'bar']);

    $results = $this->buildSearch(NULL, ['keywords,orange'])->execute();
    $this->assertResults([1, 2, 5], $results, 'Filter query 1 on multi-valued field');

    $conditions = [
      'keywords,orange',
      'keywords,apple',
    ];
    $results = $this->buildSearch(NULL, $conditions)->execute();
    $this->assertResults([2], $results, 'Filter query 2 on multi-valued field');

    $results = $this->buildSearch()->addCondition('keywords', 'orange', '<>')->execute();
    $this->assertResults([3, 4], $results, 'Negated filter on multi-valued field');

    $results = $this->buildSearch()->addCondition('keywords', NULL)->execute();
    $this->assertResults([3], $results, 'Query with NULL filter');

    $results = $this->buildSearch()->addCondition('keywords', NULL, '<>')->execute();
    $this->assertResults([1, 2, 4, 5], $results, 'Query with NOT NULL filter');
  }

  /**
   * Checks that an unknown operator throws an exception.
   */
  protected function checkUnknownOperator() {
    try {
      $this->buildSearch()
        ->addCondition('id', 1, '!=')
        ->execute();
      $this->fail('Unknown operator "!=" did not throw an exception.');
    }
    catch (SearchApiException $e) {
      $this->assertTrue(TRUE, 'Unknown operator "!=" threw an exception.');
    }
  }

  /**
   * Checks whether the module's specific alter hooks work correctly.
   */
  protected function checkDbQueryAlter() {
    $query = $this->buildSearch();
    $query->setOption('search_api_test_db_search_api_db_query_alter', TRUE);
    $results = $query->execute();
    $this->assertResults([], $results, 'Query triggering custom alter hook');
  }

  /**
   * Checks that field ID changes are treated correctly (without re-indexing).
   */
  protected function checkFieldIdChanges() {
    $this->getIndex()
      ->renameField('type', 'foobar')
      ->save();

    $results = $this->buildSearch(NULL, ['foobar,item'])->execute();
    $this->assertResults([1, 2, 3], $results, 'Search after renaming a field.');
    $this->getIndex()->renameField('foobar', 'type')->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkSecondServer() {
    /** @var \Drupal\search_api\ServerInterface $second_server */
    $second_server = Server::create([
      'id' => 'test2',
      'backend' => 'search_api_db',
      'backend_config' => [
        'database' => 'default:default',
      ],
    ]);
    $second_server->save();
    $query = $this->buildSearch();
    try {
      $second_server->search($query);
      $this->fail('Could execute a query for an index on a different server.');
    }
    catch (SearchApiException $e) {
      $this->assertTrue(TRUE, 'Executing a query for an index on a different server throws an exception.');
    }
    $second_server->delete();
  }

  /**
   * Tests the case-sensitivity of fulltext searches.
   *
   * @see https://www.drupal.org/node/2557291
   */
  protected function regressionTest2557291() {
    $results = $this->buildSearch('case')->execute();
    $this->assertResults([1], $results, 'Search for lowercase "case"');

    $results = $this->buildSearch('Case')->execute();
    $this->assertResults([1, 3], $results, 'Search for capitalized "Case"');

    $results = $this->buildSearch('CASE')->execute();
    $this->assertResults([], $results, 'Search for non-existent uppercase version of "CASE"');

    $results = $this->buildSearch('föö')->execute();
    $this->assertResults([1], $results, 'Search for keywords with umlauts');

    $results = $this->buildSearch('smile' . json_decode('"\u1F601"'))->execute();
    $this->assertResults([1], $results, 'Search for keywords with umlauts');

    $results = $this->buildSearch()->addCondition('keywords', 'grape', '<>')->execute();
    $this->assertResults([1, 3], $results, 'Negated filter on multi-valued field');
  }

  /**
   * Tests searching for multiple two-letter words.
   *
   * @see https://www.drupal.org/node/2511860
   */
  protected function regressionTest2511860() {
    $query = $this->buildSearch();
    $query->addCondition('body', 'ab xy');
    $results = $query->execute();
    $this->assertEquals(5, $results->getResultCount(), 'Fulltext filters on short words do not change the result.');

    $query = $this->buildSearch();
    $query->addCondition('body', 'ab ab');
    $results = $query->execute();
    $this->assertEquals(5, $results->getResultCount(), 'Fulltext filters on duplicate short words do not change the result.');
  }

  /**
   * Tests changing a field boost to a floating point value.
   *
   * @see https://www.drupal.org/node/2846932
   */
  protected function regressionTest2846932() {
    $index = $this->getIndex();
    $index->getField('body')->setBoost(0.8);
    $index->save();
  }

  /**
   * Tests indexing of text tokens with leading/trailing whitespace.
   *
   * @see https://www.drupal.org/node/2926733
   */
  protected function regressionTest2926733() {
    $index = $this->getIndex();
    $item_id = $this->getItemIds([1])[0];
    $fields_helper = \Drupal::getContainer()
      ->get('search_api.fields_helper');
    $item = $fields_helper->createItem($index, $item_id);
    $field = clone $index->getField('body');
    $value = new TextValue('test');
    $tokens = [];
    foreach (['test', ' test', '  test', 'test  ', ' test '] as $token) {
      $tokens[] = new TextToken($token);
    }
    $value->setTokens($tokens);
    $field->setValues([$value]);
    $item->setFields([
      'body' => $field,
    ]);
    $item->setFieldsExtracted(TRUE);
    $index->getServerInstance()->indexItems($index, [$item_id => $item]);

    // Make sure to re-index the proper version of the item to avoid confusing
    // the other tests.
    list($datasource_id, $raw_id) = Utility::splitCombinedId($item_id);
    $index->trackItemsUpdated($datasource_id, [$raw_id]);
    $this->indexItems($index->id());
  }

  /**
   * Tests indexing of items with boost.
   *
   * @see https://www.drupal.org/node/2938646
   */
  protected function regressionTest2938646() {
    $db_info = $this->getIndexDbInfo();
    $text_table = $db_info['field_tables']['body']['table'];
    $item_id = $this->getItemIds([1])[0];
    $select = \Drupal::database()->select($text_table, 't');
    $select
      ->fields('t', ['score'])
      ->condition('item_id', $item_id)
      ->condition('word', 'test');
    $select2 = clone $select;

    // Check old score.
    $old_score = $select
      ->execute()
      ->fetchField();
    $this->assertNotSame(FALSE, $old_score);
    $this->assertGreaterThan(0, $old_score);

    // Re-index item with higher boost.
    $index = $this->getIndex();
    $item = $this->container->get('search_api.fields_helper')
      ->createItem($index, $item_id);
    $item->setBoost(2);
    $indexed_ids = $this->indexItemDirectly($index, $item);
    $this->assertEquals([$item_id], $indexed_ids);

    // Verify the field scores changed accordingly.
    $new_score = $select2
      ->execute()
      ->fetchField();
    $this->assertNotSame(FALSE, $new_score);
    $this->assertEquals(2 * $old_score, $new_score);
  }

  /**
   * Tests changing of field types.
   *
   * @see https://www.drupal.org/node/2925464
   */
  protected function regressionTest2925464() {
    $index = $this->getIndex();

    $index->getField('category')->setType('integer');
    $index->save();

    $index->getField('category')->setType('string');
    $index->save();

    $this->indexItems($index->id());
  }

  /**
   * Tests changing of field types.
   *
   * @see https://www.drupal.org/project/search_api/issues/2994022
   */
  protected function regressionTest2994022() {
    $query = $this->buildSearch('nonexistent_search_term');
    $facets['category'] = [
      'field' => 'category',
      'limit' => 0,
      'min_count' => 0,
      'missing' => FALSE,
      'operator' => 'and',
    ];
    $query->setOption('search_api_facets', $facets);
    $results = $query->execute();
    $this->assertResults([], $results, 'Non-existent keyword');
    $expected = [
      ['count' => 0, 'filter' => '"article_category"'],
      ['count' => 0, 'filter' => '"item_category"'],
    ];
    $category_facets = $results->getExtraData('search_api_facets')['category'];
    usort($category_facets, [$this, 'facetCompare']);
    $this->assertEquals($expected, $category_facets, 'Correct facets were returned for minimum count 0');

    $query = $this->buildSearch('nonexistent_search_term');
    $conditions = $query->createConditionGroup('AND', ['facet:category']);
    $conditions->addCondition('category', 'article_category');
    $query->addConditionGroup($conditions);
    $facets['category'] = [
      'field' => 'category',
      'limit' => 0,
      'min_count' => 0,
      'missing' => FALSE,
      'operator' => 'and',
    ];
    $query->setOption('search_api_facets', $facets);
    $results = $query->execute();
    $this->assertResults([], $results, 'Non-existent keyword with filter');
    $expected = [
      ['count' => 0, 'filter' => '"article_category"'],
      ['count' => 0, 'filter' => '"item_category"'],
    ];
    $category_facets = $results->getExtraData('search_api_facets')['category'];
    usort($category_facets, [$this, 'facetCompare']);
    $this->assertEquals($expected, $category_facets, 'Correct facets were returned for minimum count 0');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkIndexWithoutFields() {
    $index = parent::checkIndexWithoutFields();

    $expected = [
      'search_api_datasource',
      'search_api_language',
    ];
    $db_info = $this->getIndexDbInfo($index->id());
    $info_fields = array_keys($db_info['field_tables']);
    sort($info_fields);
    $this->assertEquals($expected, $info_fields);

    return $index;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkModuleUninstall() {
    $db_info = $this->getIndexDbInfo();
    $normalized_storage_table = $db_info['index_table'];
    $field_tables = $db_info['field_tables'];

    // See whether clearing the server works.
    // Regression test for #2156151.
    $server = $this->getServer();
    $index = $this->getIndex();
    $server->deleteAllIndexItems($index);
    $query = $this->buildSearch();
    $results = $query->execute();
    $this->assertEquals(0, $results->getResultCount(), 'Clearing the server worked correctly.');
    $schema = \Drupal::database()->schema();
    $table_exists = $schema->tableExists($normalized_storage_table);
    $this->assertTrue($table_exists, 'The index tables were left in place.');

    // See whether disabling the index correctly removes all of its tables.
    $index->disable()->save();
    $db_info = $this->getIndexDbInfo();
    $this->assertNull($db_info, 'The index was successfully removed from the server.');
    $table_exists = $schema->tableExists($normalized_storage_table);
    $this->assertFalse($table_exists, 'The index tables were deleted.');
    foreach ($field_tables as $field_table) {
      $table_exists = $schema->tableExists($field_table['table']);
      $this->assertFalse($table_exists, "Field table {$field_table['table']} was successfully deleted.");
    }
    $index->enable()->save();

    // Remove first the index and then the server.
    $index->setServer();
    $index->save();

    $db_info = $this->getIndexDbInfo();
    $this->assertNull($db_info, 'The index was successfully removed from the server.');
    $table_exists = $schema->tableExists($normalized_storage_table);
    $this->assertFalse($table_exists, 'The index tables were deleted.');
    foreach ($field_tables as $field_table) {
      $table_exists = $schema->tableExists($field_table['table']);
      $this->assertFalse($table_exists, "Field table {$field_table['table']} was successfully deleted.");
    }

    // Re-add the index to see if the associated tables are also properly
    // removed when the server is deleted.
    $index->setServer($server);
    $index->save();
    $server->delete();

    $db_info = $this->getIndexDbInfo();
    $this->assertNull($db_info, 'The index was successfully removed from the server.');
    $table_exists = $schema->tableExists($normalized_storage_table);
    $this->assertFalse($table_exists, 'The index tables were deleted.');
    foreach ($field_tables as $field_table) {
      $table_exists = $schema->tableExists($field_table['table']);
      $this->assertFalse($table_exists, "Field table {$field_table['table']} was successfully deleted.");
    }

    // Uninstall the module.
    \Drupal::service('module_installer')->uninstall(['search_api_db'], FALSE);
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('search_api_db'), 'The Database Search module was successfully uninstalled.');

    $tables = $schema->findTables('search_api_db_%');
    $expected = [
      'search_api_db_database_search_index' => 'search_api_db_database_search_index',
    ];
    $this->assertEquals($expected, $tables, 'All the tables of the Database Search module have been removed.');
  }

  /**
   * Retrieves the database information for the test index.
   *
   * @param string|null $index_id
   *   (optional) The ID of the index whose database information should be
   *   retrieved.
   *
   * @return array
   *   The database information stored by the backend for the test index.
   */
  protected function getIndexDbInfo($index_id = NULL) {
    $index_id = $index_id ?: $this->indexId;
    return \Drupal::keyValue(Database::INDEXES_KEY_VALUE_STORE_ID)
      ->get($index_id);
  }

  /**
   * Indexes an item directly.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index to index the item on.
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The item.
   *
   * @return string[]
   *   The successfully indexed IDs.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if indexing failed.
   */
  protected function indexItemDirectly(IndexInterface $index, ItemInterface $item) {
    $items = [$item->getId() => $item];

    // Minimalistic version of code copied from
    // \Drupal\search_api\Entity\Index::indexSpecificItems().
    $index->alterIndexedItems($items);
    \Drupal::moduleHandler()->alter('search_api_index_items', $index, $items);
    foreach ($items as $item) {
      // This will cache the extracted fields so processors, etc., can retrieve
      // them directly.
      $item->getFields();
    }
    $index->preprocessIndexItems($items);

    $indexed_ids = [];
    if ($items) {
      $indexed_ids = $index->getServerInstance()->indexItems($index, $items);
    }
    return $indexed_ids;
  }

  /**
   * Tests whether a server on a non-default database is handled correctly.
   */
  public function testNonDefaultDatabase() {
    // Clone the primary credentials to a replica connection.
    // Note this will result in two independent connection objects that happen
    // to point to the same place.
    // @see \Drupal\KernelTests\Core\Database\ConnectionTest::testConnectionRouting()
    $connection_info = CoreDatabase::getConnectionInfo('default');
    CoreDatabase::addConnectionInfo('default', 'replica', $connection_info['default']);

    $db1 = CoreDatabase::getConnection('default', 'default');
    $db2 = CoreDatabase::getConnection('replica', 'default');

    // Safety checks copied from the Core test, if these fail something is wrong
    // with Core.
    $this->assertNotNull($db1, 'default connection is a real connection object.');
    $this->assertNotNull($db2, 'replica connection is a real connection object.');
    $this->assertNotSame($db1, $db2, 'Each target refers to a different connection.');

    // Create backends based on each of the two targets and verify they use the
    // right connections.
    $config = [
      'database' => 'default:default',
    ];
    $backend1 = Database::create($this->container, $config, '', []);
    $config['database'] = 'default:replica';
    $backend2 = Database::create($this->container, $config, '', []);

    $this->assertSame($db1, $backend1->getDatabase());
    $this->assertSame($db2, $backend2->getDatabase());

    // Make sure they also use different DBMS compatibility handlers, which also
    // use the correct database connections.
    $dbms_comp1 = $backend1->getDbmsCompatibilityHandler();
    $dbms_comp2 = $backend2->getDbmsCompatibilityHandler();
    $this->assertNotSame($dbms_comp1, $dbms_comp2);
    $this->assertSame($db1, $dbms_comp1->getDatabase());
    $this->assertSame($db2, $dbms_comp2->getDatabase());

    // Finally, make sure the DBMS compatibility handlers also have the correct
    // classes (meaning we used the correct one and didn't just fall back to the
    // generic database).
    $service = $this->container->get('search_api_db.database_compatibility');
    $database_type = $db1->databaseType();
    $service_id = "$database_type.search_api_db.database_compatibility";
    $service2 = $this->container->get($service_id);
    $this->assertSame($service2, $service);
    $class = get_class($service);
    $this->assertNotEquals(GenericDatabase::class, $class);
    $this->assertSame($dbms_comp1, $service);
    $this->assertEquals($class, get_class($dbms_comp2));
  }

  /**
   * Tests whether indexing of dates works correctly.
   */
  public function testDateIndexing() {
    // Load all existing entities.
    $storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');
    $storage->delete($storage->loadMultiple());

    $index = Index::load('database_search_index');
    $index->getField('name')->setType('date');
    $index->save();

    // Simulate date field creation in one timezone and indexing in another.
    date_default_timezone_set('America/Chicago');

    // Test different input values, similar to @dataProvider (but with less
    // overhead).
    $t = 1400000000;
    $date_time_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $date_format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    $test_values = [
      'null' => [NULL, NULL],
      'timestamp' => [$t, $t],
      'string timestamp' => ["$t", $t],
      'float timestamp' => [$t + 0.12, $t],
      'date string' => [gmdate($date_time_format, $t), $t],
      'date string with timezone' => [date($date_time_format . 'P', $t), $t],
      'date only' => [
        date($date_format, $t),
        // Date-only fields are stored with the default time (12:00:00).
        strtotime(date($date_format, $t) . 'T12:00:00+00:00'),
      ],
    ];

    // Get storage information for quickly checking the indexed value.
    $db_info = $this->getIndexDbInfo();
    $table = $db_info['index_table'];
    $column = $db_info['field_tables']['name']['column'];
    $sql = "SELECT $column FROM {{$table}} WHERE item_id = :id";

    $id = 0;
    date_default_timezone_set('Asia/Seoul');
    foreach ($test_values as $label => list($field_value, $expected)) {
      $entity = $this->addTestEntity(++$id, [
        'name' => $field_value,
        'type' => 'item',
      ]);
      $item_id = $this->getItemIds([$id])[0];
      $index->indexSpecificItems([$item_id => $entity->getTypedData()]);
      $args[':id'] = $item_id;
      $indexed_value = \Drupal::database()->query($sql, $args)->fetchField();
      if ($expected === NULL) {
        $this->assertSame($expected, $indexed_value, "Indexing of date field with $label value.");
      }
      else {
        $this->assertEquals($expected, $indexed_value, "Indexing of date field with $label value.");
      }
    }
  }

}
