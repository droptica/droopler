<?php

namespace Drupal\search_api_db\Plugin\search_api\backend;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database as CoreDatabase;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\DataType\DataTypePluginManager;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Plugin\search_api\data_type\value\TextToken;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\DataTypeHelper;
use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Suggestion\SuggestionFactory;
use Drupal\search_api_db\DatabaseCompatibility\DatabaseCompatibilityHandlerInterface;
use Drupal\search_api_db\DatabaseCompatibility\GenericDatabase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Indexes and searches items using the database.
 *
 * Database SELECT queries issued by this service class will be marked with tags
 * according to their context. The following are used:
 * - search_api_db_search: For all queries that are based on a search query.
 * - search_api_db_facets_base: For the query which creates a temporary results
 *   table to be used for facetting. (Is always used in conjunction with
 *   "search_api_db_search".)
 * - search_api_db_facet: For queries on the temporary results table for
 *   determining the items of a specific facet.
 * - search_api_db_facet_all: For queries to return all indexed values for a
 *   specific field. Is used when a facet has a "min_count" of 0.
 * - search_api_db_autocomplete: For queries which create a temporary results
 *   table to be used for computing autocomplete suggestions. (Is always used in
 *   conjunction with "search_api_db_search".)
 *
 * The following metadata will be present for those SELECT queries:
 * - search_api_query: The Search API query object. (Always present.)
 * - search_api_db_fields: Internal storage information for the indexed fields,
 *   as used by this service class. (Always present.)
 * - search_api_db_facet: The settings array of the facet currently being
 *   computed. (Present for "search_api_db_facet" and "search_api_db_facet_all"
 *   queries.)
 * - search_api_db_autocomplete: An array containing the parameters of the
 *   getAutocompleteSuggestions() call, except "query". (Present for
 *   "search_api_db_autocomplete" queries.)
 *
 * @SearchApiBackend(
 *   id = "search_api_db",
 *   label = @Translation("Database"),
 *   description = @Translation("Indexes items in the database. Supports several advanced features, but should not be used for large sites.")
 * )
 */
class Database extends BackendPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * Multiplier for scores to have precision when converted from float to int.
   */
  const SCORE_MULTIPLIER = 1000;

  /**
   * The ID of the key-value store in which the indexes' DB infos are stored.
   */
  const INDEXES_KEY_VALUE_STORE_ID = 'search_api_db.indexes';

  /**
   * The database connection to use for this server.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * DBMS compatibility handler for this type of database.
   *
   * @var \Drupal\search_api_db\DatabaseCompatibility\DatabaseCompatibilityHandlerInterface
   */
  protected $dbmsCompatibility;

  /**
   * The module handler to use.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|null
   */
  protected $moduleHandler;

  /**
   * The config factory to use.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * The data type plugin manager to use.
   *
   * @var \Drupal\search_api\DataType\DataTypePluginManager
   */
  protected $dataTypePluginManager;

  /**
   * The key-value store to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * The transliteration service to use.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliterator;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface|null
   */
  protected $dateFormatter;

  /**
   * The data type helper.
   *
   * @var \Drupal\search_api\Utility\DataTypeHelper|null
   */
  protected $dataTypeHelper;

  /**
   * The keywords ignored during the current search query.
   *
   * @var array
   */
  protected $ignored = [];

  /**
   * All warnings for the current search query.
   *
   * @var array
   */
  protected $warnings = [];

  /**
   * Constructs a Database object.
   *
   * @param array $configuration
   *   A configuration array containing settings for this backend.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (isset($configuration['database'])) {
      list($key, $target) = explode(':', $configuration['database'], 2);
      // @todo Can we somehow get the connection in a dependency-injected way?
      $this->database = CoreDatabase::getConnection($target, $key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $backend */
    $backend = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $backend->setModuleHandler($container->get('module_handler'));
    $backend->setConfigFactory($container->get('config.factory'));
    $backend->setDataTypePluginManager($container->get('plugin.manager.search_api.data_type'));
    $backend->setLogger($container->get('logger.channel.search_api_db'));
    $backend->setKeyValueStore($container->get('keyvalue')->get(self::INDEXES_KEY_VALUE_STORE_ID));
    $backend->setDateFormatter($container->get('date.formatter'));
    $backend->setDataTypeHelper($container->get('search_api.data_type_helper'));

    // For a new backend plugin, the database might not be set yet. In that case
    // we of course also don't need a DBMS compatibility handler.
    $database = $backend->getDatabase();
    if ($database) {
      $dbms_compatibility_handler = $container->get('search_api_db.database_compatibility');
      // Make sure that we actually provide a handler for the right database,
      // otherwise create the right service manually. (This is the case if the
      // user didn't pick the default database.)
      if ($dbms_compatibility_handler->getDatabase() != $database) {
        $database_type = $database->databaseType();
        $service_id = "$database_type.search_api_db.database_compatibility";
        if ($container->has($service_id)) {
          /** @var \Drupal\search_api_db\DatabaseCompatibility\DatabaseCompatibilityHandlerInterface $dbms_compatibility_handler */
          $dbms_compatibility_handler = $container->get($service_id);
          $dbms_compatibility_handler = $dbms_compatibility_handler->getCloneForDatabase($database);
        }
        else {
          $dbms_compatibility_handler = new GenericDatabase($database, $container->get('transliteration'));
        }
      }
      $backend->setDbmsCompatibilityHandler($dbms_compatibility_handler);
    }

    return $backend;
  }

  /**
   * Retrieves the database connection used by this backend.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    return $this->database;
  }

  /**
   * Returns the module handler to use for this plugin.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function getModuleHandler() {
    return $this->moduleHandler ?: \Drupal::moduleHandler();
  }

  /**
   * Sets the module handler to use for this plugin.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to use for this plugin.
   *
   * @return $this
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    return $this;
  }

  /**
   * Returns the config factory to use for this plugin.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  public function getConfigFactory() {
    return $this->configFactory ?: \Drupal::configFactory();
  }

  /**
   * Sets the config factory to use for this plugin.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to use for this plugin.
   *
   * @return $this
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * Retrieves the data type plugin manager.
   *
   * @return \Drupal\search_api\DataType\DataTypePluginManager
   *   The data type plugin manager.
   */
  public function getDataTypePluginManager() {
    return $this->dataTypePluginManager ?: \Drupal::service('plugin.manager.search_api.data_type');
  }

  /**
   * Sets the data type plugin manager.
   *
   * @param \Drupal\search_api\DataType\DataTypePluginManager $data_type_plugin_manager
   *   The new data type plugin manager.
   *
   * @return $this
   */
  public function setDataTypePluginManager(DataTypePluginManager $data_type_plugin_manager) {
    $this->dataTypePluginManager = $data_type_plugin_manager;
    return $this;
  }

  /**
   * Retrieves the key-value store to use.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   The key-value store.
   */
  public function getKeyValueStore() {
    return $this->keyValueStore ?: \Drupal::keyValue(self::INDEXES_KEY_VALUE_STORE_ID);
  }

  /**
   * Sets the key-value store to use.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value_store
   *   The key-value store.
   *
   * @return $this
   */
  public function setKeyValueStore(KeyValueStoreInterface $key_value_store) {
    $this->keyValueStore = $key_value_store;
    return $this;
  }

  /**
   * Retrieves the date formatter.
   *
   * @return \Drupal\Core\Datetime\DateFormatterInterface
   *   The date formatter.
   */
  public function getDateFormatter() {
    return $this->dateFormatter ?: \Drupal::service('date.formatter');
  }

  /**
   * Sets the date formatter.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The new date formatter.
   *
   * @return $this
   */
  public function setDateFormatter(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
    return $this;
  }

  /**
   * Retrieves the data type helper.
   *
   * @return \Drupal\search_api\Utility\DataTypeHelper
   *   The data type helper.
   */
  public function getDataTypeHelper() {
    return $this->dataTypeHelper ?: \Drupal::service('search_api.data_type_helper');
  }

  /**
   * Sets the data type helper.
   *
   * @param \Drupal\search_api\Utility\DataTypeHelper $data_type_helper
   *   The new data type helper.
   *
   * @return $this
   */
  public function setDataTypeHelper(DataTypeHelper $data_type_helper) {
    $this->dataTypeHelper = $data_type_helper;
    return $this;
  }

  /**
   * Retrieves the DBMS compatibility handler.
   *
   * @return \Drupal\search_api_db\DatabaseCompatibility\DatabaseCompatibilityHandlerInterface
   *   The DBMS compatibility handler.
   */
  public function getDbmsCompatibilityHandler() {
    return $this->dbmsCompatibility;
  }

  /**
   * Sets the DBMS compatibility handler.
   *
   * @param \Drupal\search_api_db\DatabaseCompatibility\DatabaseCompatibilityHandlerInterface $handler
   *   The DBMS compatibility handler.
   *
   * @return $this
   */
  protected function setDbmsCompatibilityHandler(DatabaseCompatibilityHandlerInterface $handler) {
    $this->dbmsCompatibility = $handler;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'database' => NULL,
      'min_chars' => 1,
      'matching' => 'words',
      'autocomplete' => [
        'suggest_suffix' => TRUE,
        'suggest_words' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Discern between creation and editing of a server, since we don't allow
    // the database to be changed later on.
    if (!$this->configuration['database']) {
      $options = [];
      $key = $target = '';
      foreach (CoreDatabase::getAllConnectionInfo() as $key => $targets) {
        foreach ($targets as $target => $info) {
          $options[$key]["$key:$target"] = "$key » $target";
        }
      }
      if (count($options) > 1 || count(reset($options)) > 1) {
        $form['database'] = [
          '#type' => 'select',
          '#title' => $this->t('Database'),
          '#description' => $this->t('Select the database key and target to use for storing indexing information in. Cannot be changed after creation.'),
          '#options' => $options,
          '#default_value' => 'default:default',
          '#required' => TRUE,
        ];
      }
      else {
        $form['database'] = [
          '#type' => 'value',
          '#value' => "$key:$target",
        ];
      }
    }
    else {
      $form = [
        'database' => [
          '#type' => 'value',
          '#title' => $this->t('Database'),
          '#value' => $this->configuration['database'],
        ],
        'database_text' => [
          '#type' => 'item',
          '#title' => $this->t('Database'),
          '#plain_text' => str_replace(':', ' > ', $this->configuration['database']),
          '#input' => FALSE,
        ],
      ];
    }

    $form['min_chars'] = [
      '#type' => 'select',
      '#title' => $this->t('Minimum word length'),
      '#description' => $this->t('The minimum number of characters a word must consist of to be indexed'),
      '#options' => array_combine(
        [1, 2, 3, 4, 5, 6],
        [1, 2, 3, 4, 5, 6]
      ),
      '#default_value' => $this->configuration['min_chars'],
    ];

    $form['matching'] = [
      '#type' => 'radios',
      '#title' => $this->t('Partial matching'),
      '#default_value' => $this->configuration['matching'],
      '#options' => [
        'words' => $this->t('Match whole words only'),
        'partial' => $this->t('Match on parts of a word'),
        'prefix' => $this->t('Match words starting with given keywords'),
      ],
    ];

    if ($this->getModuleHandler()->moduleExists('search_api_autocomplete')) {
      $form['autocomplete'] = [
        '#type' => 'details',
        '#title' => $this->t('Autocomplete settings'),
        '#description' => $this->t('These settings allow you to configure how suggestions are computed when autocompletion is used. If you are seeing many inappropriate suggestions you might want to deactivate the corresponding suggestion type. You can also deactivate one method to speed up the generation of suggestions.'),
      ];
      $form['autocomplete']['suggest_suffix'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Suggest word endings'),
        '#description' => $this->t('Suggest endings for the currently entered word.'),
        '#default_value' => $this->configuration['autocomplete']['suggest_suffix'],
      ];
      $form['autocomplete']['suggest_words'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Suggest additional words'),
        '#description' => $this->t('Suggest additional words the user might want to search for.'),
        '#default_value' => $this->configuration['autocomplete']['suggest_words'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    $info = [];

    $info[] = [
      'label' => $this->t('Database'),
      'info' => str_replace(':', ' > ', $this->configuration['database']),
    ];
    if ($this->configuration['min_chars'] > 1) {
      $info[] = [
        'label' => $this->t('Minimum word length'),
        'info' => $this->configuration['min_chars'],
      ];
    }

    $labels = [
      'words' => $this->t('Match whole words only'),
      'partial' => $this->t('Match on parts of a word'),
      'prefix' => $this->t('Match words starting with given keywords'),
    ];
    $info[] = [
      'label' => $this->t('Partial matching'),
      'info' => $labels[$this->configuration['matching']],
    ];

    if (!empty($this->configuration['autocomplete'])) {
      $this->configuration['autocomplete'] += [
        'suggest_suffix' => TRUE,
        'suggest_words' => TRUE,
      ];
      $autocomplete_modes = [];
      if ($this->configuration['autocomplete']['suggest_suffix']) {
        $autocomplete_modes[] = $this->t('Suggest word endings');
      }
      if ($this->configuration['autocomplete']['suggest_words']) {
        $autocomplete_modes[] = $this->t('Suggest additional words');
      }
      $autocomplete_modes = $autocomplete_modes ? implode('; ', $autocomplete_modes) : $this->t('none');
      $info[] = [
        'label' => $this->t('Autocomplete suggestions'),
        'info' => $autocomplete_modes,
      ];
    }

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedFeatures() {
    return [
      'search_api_autocomplete',
      'search_api_facets',
      'search_api_facets_operator_or',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function postUpdate() {
    if (empty($this->server->original)) {
      // When in doubt, opt for the safer route and reindex.
      return TRUE;
    }
    $original_config = $this->server->original->getBackendConfig();
    $original_config += $this->defaultConfiguration();
    return $this->configuration['min_chars'] != $original_config['min_chars'];
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete() {
    $schema = $this->database->schema();

    $key_value_store = $this->getKeyValueStore();
    foreach ($key_value_store->getAll() as $index_id => $db_info) {
      if ($db_info['server'] != $this->server->id()) {
        continue;
      }

      // Delete the regular field tables.
      foreach ($db_info['field_tables'] as $field) {
        if ($schema->tableExists($field['table'])) {
          $schema->dropTable($field['table']);
        }
      }

      // Delete the denormalized field tables.
      if ($schema->tableExists($db_info['index_table'])) {
        $schema->dropTable($db_info['index_table']);
      }

      $key_value_store->delete($index_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex(IndexInterface $index) {
    try {
      // Create the denormalized table now.
      $index_table = $this->findFreeTable('search_api_db_', $index->id());
      $this->createFieldTable(NULL, ['table' => $index_table], 'index');

      $db_info = [];
      $db_info['server'] = $this->server->id();
      $db_info['field_tables'] = [];
      $db_info['index_table'] = $index_table;
      $this->getKeyValueStore()->set($index->id(), $db_info);
    }
    // The database operations might throw PDO or other exceptions, so we catch
    // them all and re-wrap them appropriately.
    catch (\Exception $e) {
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }

    // If dealing with features or stale data or whatever, we might already have
    // settings stored for this index. If we have, we should take care to only
    // change what is needed, so we don't discard indexed data unnecessarily.
    // The easiest way to do this is by just pretending the index was already
    // present, but its fields were updated.
    $this->fieldsUpdated($index);
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex(IndexInterface $index) {
    // Process field ID changes so they won't lead to reindexing.
    $renames = $index->getFieldRenames();
    if ($renames) {
      $db_info = $this->getIndexDbInfo($index);
      // We have to recreate "field_tables" from scratch in case field IDs got
      // swapped between two (or more) fields.
      $fields = [];
      foreach ($db_info['field_tables'] as $field_id => $info) {
        if (isset($renames[$field_id])) {
          $field_id = $renames[$field_id];
        }
        $fields[$field_id] = $info;
      }
      if ($fields != $db_info['field_tables']) {
        $db_info['field_tables'] = $fields;
        $this->getKeyValueStore()->set($index->id(), $db_info);
      }
    }

    // Check if any fields were updated and trigger a reindex if needed.
    if ($this->fieldsUpdated($index)) {
      $index->reindex();
    }
  }

  /**
   * Finds a free table name using a certain prefix and name base.
   *
   * Used as a helper method in fieldsUpdated().
   *
   * MySQL 5.0 imposes a 64 characters length limit for table names, PostgreSQL
   * 8.3 only allows 62 bytes. Therefore, always return a name at most 62
   * bytes long.
   *
   * @param string $prefix
   *   Prefix for the table name. Must only consist of characters valid for SQL
   *   identifiers.
   * @param string $name
   *   Name to base the table name on.
   *
   * @return string
   *   A database table name that isn't in use yet.
   */
  protected function findFreeTable($prefix, $name) {
    // A DB prefix might further reduce the maximum length of the table name.
    $max_bytes = 62;
    if ($db_prefix = $this->database->tablePrefix()) {
      // Use strlen() instead of mb_strlen() since we want to measure bytes, not
      // characters.
      $max_bytes -= strlen($db_prefix);
    }

    $base = $table = Unicode::truncateBytes($prefix . mb_strtolower(preg_replace('/[^a-z0-9]/i', '_', $name)), $max_bytes);
    $i = 0;
    while ($this->database->schema()->tableExists($table)) {
      $suffix = '_' . ++$i;
      $table = Unicode::truncateBytes($base, $max_bytes - strlen($suffix)) . $suffix;
    }
    return $table;
  }

  /**
   * Finds a free column name within a database table.
   *
   * Used as a helper method in fieldsUpdated().
   *
   * MySQL 5.0 imposes a 64 characters length limit for identifier names,
   * PostgreSQL 8.3 only allows 62 bytes. Therefore, always return a name at
   * most 62 bytes long.
   *
   * @param string $table
   *   The name of the table.
   * @param string $column
   *   The name to base the column name on.
   *
   * @return string
   *   A column name that isn't in use in the specified table yet.
   */
  protected function findFreeColumn($table, $column) {
    $maxbytes = 62;

    $base = $name = Unicode::truncateBytes(mb_strtolower(preg_replace('/[^a-z0-9]/i', '_', $column)), $maxbytes);
    // If the table does not exist yet, the initial name is not taken.
    if ($this->database->schema()->tableExists($table)) {
      $i = 0;
      while ($this->database->schema()->fieldExists($table, $name)) {
        $suffix = '_' . ++$i;
        $name = Unicode::truncateBytes($base, $maxbytes - strlen($suffix)) . $suffix;
      }
    }
    return $name;
  }

  /**
   * Creates or modifies a table to add an indexed field.
   *
   * Used as a helper method in fieldsUpdated().
   *
   * @param \Drupal\search_api\Item\FieldInterface|null $field
   *   The field to add. Or NULL if only the initial table with an "item_id"
   *   column should be created.
   * @param array $db
   *   Associative array containing the following:
   *   - table: The table to use for the field.
   *   - column: (optional) The column to use in that table. Defaults to
   *     "value". For creating a separate field table, it must be left empty!
   * @param string $type
   *   (optional) The type of table being created. Either "index" (for the
   *   denormalized table for an entire index) or "field" (for field-specific
   *   tables).
   *
   * @todo Write a test to ensure a field named "value" doesn't break this.
   */
  protected function createFieldTable(FieldInterface $field = NULL, array $db, $type = 'field') {
    $new_table = !$this->database->schema()->tableExists($db['table']);
    if ($new_table) {
      $table = [
        'name' => $db['table'],
        'module' => 'search_api_db',
        'fields' => [
          'item_id' => [
            'type' => 'varchar',
            'length' => 150,
            'description' => 'The primary identifier of the item',
            'not null' => TRUE,
          ],
        ],
      ];
      // For the denormalized index table, add a primary key right away. For
      // newly created field tables we first need to add the "value" column.
      if ($type === 'index') {
        $table['primary key'] = ['item_id'];
      }
      $this->database->schema()->createTable($db['table'], $table);
      $this->dbmsCompatibility->alterNewTable($db['table'], $type);
    }

    // Stop here if we want to create a table with just the 'item_id' column.
    if (!isset($field)) {
      return;
    }

    $column = isset($db['column']) ? $db['column'] : 'value';
    $db_field = $this->sqlType($field->getType());
    $db_field += [
      'description' => "The field's value for this item",
    ];
    if ($new_table || $type === 'field') {
      $db_field['not null'] = TRUE;
    }
    $this->database->schema()->addField($db['table'], $column, $db_field);
    if ($db_field['type'] === 'varchar') {
      $index_spec = [[$column, 10]];
    }
    else {
      $index_spec = [$column];
    }
    // Create a table specification skeleton to pass to addIndex().
    $table_spec = [
      'fields' => [
        $column => $db_field,
      ],
      'indexes' => [
        $column => $index_spec,
      ],
    ];

    // This is a quick fix for a core bug, so we can run the tests with SQLite
    // until this is fixed.
    //
    // In SQLite, indexes and tables can't have the same name, which is
    // the case for Search API DB. We have following situation:
    // - a table named search_api_db_default_index_title
    // - a table named search_api_db_default_index
    //
    // The last table has an index on the title column, which results in an
    // index with the same as the first table, which conflicts in SQLite.
    //
    // The core issue addressing this (https://www.drupal.org/node/1008128) was
    // closed as it fixed the PostgresSQL part. The SQLite fix is added in
    // https://www.drupal.org/node/2625664
    // We prevent this by adding an extra underscore (which is also the proposed
    // solution in the original core issue).
    //
    // @todo: Remove when #2625664 lands in Core. See #2625722 for a patch that
    // implements this.
    try {
      $this->database->schema()->addIndex($db['table'], '_' . $column, $index_spec, $table_spec);
    }
    catch (\PDOException $e) {
      $variables['%column'] = $column;
      $variables['%table'] = $db['table'];
      $this->logException($e, '%type while trying to add a database index for column %column to table %table: @message in %function (line %line of %file).', $variables, RfcLogLevel::WARNING);
    }
    catch (DatabaseException $e) {
      $variables['%column'] = $column;
      $variables['%table'] = $db['table'];
      $this->logException($e, '%type while trying to add a database index for column %column to table %table: @message in %function (line %line of %file).', $variables, RfcLogLevel::WARNING);
    }

    // Add a covering index for field tables.
    if ($new_table && $type == 'field') {
      $this->database->schema()->addPrimaryKey($db['table'], ['item_id', $column]);
    }
  }

  /**
   * Returns the schema definition for a database column for a search data type.
   *
   * @param string $type
   *   An indexed field's search type. One of the default data types.
   *
   * @return array
   *   Column configurations to use for the field's database column.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if $type is unknown.
   */
  protected function sqlType($type) {
    switch ($type) {
      case 'text':
        return ['type' => 'varchar', 'length' => 30];
      case 'string':
      case 'uri':
        return ['type' => 'varchar', 'length' => 255];

      case 'integer':
      case 'duration':
      case 'date':
        // 'datetime' sucks. Therefore, we just store the timestamp.
        return ['type' => 'int', 'size' => 'big'];

      case 'decimal':
        return ['type' => 'float'];

      case 'boolean':
        return ['type' => 'int', 'size' => 'tiny'];

      default:
        throw new SearchApiException("Unknown field type '$type'.");
    }
  }

  /**
   * Updates the storage tables when the field configuration changes.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index whose fields (might) have changed.
   *
   * @return bool
   *   TRUE if the data needs to be reindexed, FALSE otherwise.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if any exceptions occur internally – for example, in the database
   *   layer.
   */
  protected function fieldsUpdated(IndexInterface $index) {
    try {
      $db_info = $this->getIndexDbInfo($index);
      $fields = &$db_info['field_tables'];
      $new_fields = $index->getFields();
      $new_fields += $this->getSpecialFields($index);

      $reindex = FALSE;
      $cleared = FALSE;
      $text_table = NULL;
      $denormalized_table = $db_info['index_table'];

      foreach ($fields as $field_id => $field) {
        $was_text_type = $this->getDataTypeHelper()->isTextType($field['type']);
        if (!isset($text_table) && $was_text_type) {
          // Stash the shared text table name for the index.
          $text_table = $field['table'];
        }

        if (!isset($new_fields[$field_id])) {
          // The field is no longer in the index, drop the data.
          $this->removeFieldStorage($field_id, $field, $denormalized_table);
          unset($fields[$field_id]);
          continue;
        }
        $old_type = $field['type'];
        $new_type = $new_fields[$field_id]->getType();
        $fields[$field_id]['type'] = $new_type;
        $fields[$field_id]['boost'] = $new_fields[$field_id]->getBoost();
        if ($old_type != $new_type) {
          $is_text_type = $this->getDataTypeHelper()->isTextType($new_type);
          if ($was_text_type || $is_text_type) {
            // A change in fulltext status necessitates completely clearing the
            // index.
            $reindex = TRUE;
            if (!$cleared) {
              $cleared = TRUE;
              $this->deleteAllIndexItems($index);
            }
            $this->removeFieldStorage($field_id, $field, $denormalized_table);
            // Keep the table in $new_fields to create the new storage.
            continue;
          }
          elseif ($this->sqlType($old_type) != $this->sqlType($new_type)) {
            // There is a change in SQL type. We don't have to clear the index,
            // since types can be converted.
            $sql_spec = $this->sqlType($new_type);
            $sql_spec += [
              'description' => "The field's value for this item",
            ];
            $this->database->schema()->changeField($denormalized_table, $field['column'], $field['column'], $sql_spec);
            $sql_spec['not null'] = TRUE;
            $this->database->schema()->changeField($field['table'], 'value', 'value', $sql_spec);
            $reindex = TRUE;
          }
          elseif ($old_type == 'date' || $new_type == 'date') {
            // Even though the SQL type stays the same, we have to reindex since
            // conversion rules change.
            $reindex = TRUE;
          }
        }
        elseif ($was_text_type && $field['boost'] != $new_fields[$field_id]->getBoost()) {
          if (!$reindex) {
            // If there was a non-zero boost set previously, we can just update
            // all scores with a single UPDATE query. Otherwise, no way around
            // re-indexing.
            if ($field['boost']) {
              $multiplier = $new_fields[$field_id]->getBoost() / $field['boost'];
              // Postgres doesn't allow multiplying an integer column with a
              // float literal, so we have to work around that.
              $expression = 'score * :mult';
              $args = [
                ':mult' => $multiplier,
              ];
              if (is_float($multiplier) && $pos = strpos("$multiplier", '.')) {
                $expression .= ' / :div';
                $after_point_digits = strlen("$multiplier") - $pos - 1;
                $args[':div'] = pow(10, min(3, $after_point_digits));
                $args[':mult'] = (int) round($args[':mult'] * $args[':div']);
              }
              $this->database->update($text_table)
                ->expression('score', $expression, $args)
                ->condition('field_name', static::getTextFieldName($field_id))
                ->execute();
            }
            else {
              $reindex = TRUE;
            }
          }
        }

        // Make sure the table and column now exist. (Especially important when
        // we actually add the index for the first time.)
        $storage_exists = empty($field['table']) || $this->database->schema()
          ->fieldExists($field['table'], 'value');
        $denormalized_storage_exists = $this->database->schema()
          ->fieldExists($denormalized_table, $field['column']);
        if (!$was_text_type && !$storage_exists) {
          $db = [
            'table' => $field['table'],
          ];
          $this->createFieldTable($new_fields[$field_id], $db);
        }
        // Ensure that a column is created in the denormalized storage even for
        // 'text' fields.
        if (!$denormalized_storage_exists) {
          $db = [
            'table' => $denormalized_table,
            'column' => $field['column'],
          ];
          $this->createFieldTable($new_fields[$field_id], $db, 'index');
        }
        unset($new_fields[$field_id]);
      }

      $prefix = 'search_api_db_' . $index->id();
      // These are new fields that were previously not indexed.
      foreach ($new_fields as $field_id => $field) {
        $reindex = TRUE;
        $fields[$field_id] = [];
        if ($this->getDataTypeHelper()->isTextType($field->getType())) {
          if (!isset($text_table)) {
            // If we have not encountered a text table, assign a name for it.
            $text_table = $this->findFreeTable($prefix . '_', 'text');
          }
          $fields[$field_id]['table'] = $text_table;
        }
        else {
          $fields[$field_id]['table'] = $this->findFreeTable($prefix . '_', $field_id);
          $this->createFieldTable($field, $fields[$field_id]);
        }

        // Always add a column in the denormalized table.
        $fields[$field_id]['column'] = $this->findFreeColumn($denormalized_table, $field_id);
        $this->createFieldTable($field, ['table' => $denormalized_table, 'column' => $fields[$field_id]['column']], 'index');

        $fields[$field_id]['type'] = $field->getType();
        $fields[$field_id]['boost'] = $field->getBoost();
      }

      // If needed, make sure the text table exists.
      if (isset($text_table) && !$this->database->schema()->tableExists($text_table)) {
        $table = [
          'name' => $text_table,
          'module' => 'search_api_db',
          'fields' => [
            'item_id' => [
              'type' => 'varchar',
              'length' => 150,
              'description' => 'The primary identifier of the item',
              'not null' => TRUE,
            ],
            'field_name' => [
              'description' => "The name of the field in which the token appears, or a base-64 encoded sha-256 hash of the field",
              'not null' => TRUE,
              'type' => 'varchar',
              'length' => 191,
            ],
            'word' => [
              'description' => 'The text of the indexed token',
              'type' => 'varchar',
              'length' => 50,
              'not null' => TRUE,
              'binary' => TRUE,
            ],
            'score' => [
              'description' => 'The score associated with this token',
              'type' => 'int',
              'unsigned' => TRUE,
              'not null' => TRUE,
              'default' => 0,
            ],
          ],
          'indexes' => [
            'word_field' => [['word', 20], 'field_name'],
          ],
          // Add a covering index since word is not repeated for each item.
          'primary key' => ['item_id', 'field_name', 'word'],
        ];
        $this->database->schema()->createTable($text_table, $table);
        $this->dbmsCompatibility->alterNewTable($text_table, 'text');
      }

      $this->getKeyValueStore()->set($index->id(), $db_info);

      return $reindex;
    }
    // The database operations might throw PDO or other exceptions, so we catch
    // them all and re-wrap them appropriately.
    catch (\Exception $e) {
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Drops a field's table and its column from the denormalized table.
   *
   * @param string $name
   *   The field name.
   * @param array $field
   *   Backend-internal information about the field.
   * @param string $index_table
   *   The table which stores the denormalized data for this field.
   */
  protected function removeFieldStorage($name, array $field, $index_table) {
    if ($this->getDataTypeHelper()->isTextType($field['type'])) {
      // Remove data from the text table.
      $this->database->delete($field['table'])
        ->condition('field_name', static::getTextFieldName($name))
        ->execute();
    }
    elseif ($this->database->schema()->tableExists($field['table'])) {
      // Remove the field table.
      $this->database->schema()->dropTable($field['table']);
    }

    // Remove the field column from the denormalized table.
    $this->database->schema()->dropField($index_table, $field['column']);
  }

  /**
   * {@inheritdoc}
   */
  public function removeIndex($index) {
    if (!is_object($index)) {
      // If the index got deleted, create a dummy to simplify the code. Since we
      // can't know, we assume the index was read-only, just to be on the safe
      // side.
      $index = Index::create([
        'id' => $index,
        'read_only' => TRUE,
      ]);
    }

    $db_info = $this->getIndexDbInfo($index);

    try {
      if (!isset($db_info['field_tables']) && !isset($db_info['index_table'])) {
        return;
      }
      // Don't delete the index data of read-only indexes.
      if (!$index->isReadOnly()) {
        foreach ($db_info['field_tables'] as $field) {
          if ($this->database->schema()->tableExists($field['table'])) {
            $this->database->schema()->dropTable($field['table']);
          }
        }
        if ($this->database->schema()->tableExists($db_info['index_table'])) {
          $this->database->schema()->dropTable($db_info['index_table']);
        }
      }

      $this->getKeyValueStore()->delete($index->id());
    }
    // The database operations might throw PDO or other exceptions, so we catch
    // them all and re-wrap them appropriately.
    catch (\Exception $e) {
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    if (!$this->getIndexDbInfo($index)) {
      $index_id = $index->id();
      throw new SearchApiException("No field settings saved for index with ID '$index_id'.");
    }
    $indexed = [];
    foreach ($items as $id => $item) {
      try {
        $this->indexItem($index, $item);
        $indexed[] = $id;
      }
      catch (\Exception $e) {
        // We just log the error, hoping we can index the other items.
        $this->getLogger()->warning($e->getMessage());
      }
    }
    return $indexed;
  }

  /**
   * Indexes a single item on the specified index.
   *
   * Used as a helper method in indexItems().
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which the item is being indexed.
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The item to index.
   *
   * @throws \Exception
   *   Any encountered database (or other) exceptions are passed on, out of this
   *   method.
   */
  protected function indexItem(IndexInterface $index, ItemInterface $item) {
    $fields = $this->getFieldInfo($index);
    $fields_updated = FALSE;
    $field_errors = [];
    $db_info = $this->getIndexDbInfo($index);
    $denormalized_table = $db_info['index_table'];
    $item_id = $item->getId();

    $transaction = $this->database->startTransaction('search_api_db_indexing');

    try {
      // Remove the item from the denormalized table.
      $this->database->delete($denormalized_table)
        ->condition('item_id', $item_id)
        ->execute();

      $denormalized_values = [];
      $text_inserts = [];
      $item_fields = $item->getFields();
      $item_fields += $this->getSpecialFields($index, $item);
      foreach ($item_fields as $field_id => $field) {
        // Sometimes index changes are not triggering the update hooks
        // correctly. Therefore, to avoid DB errors, we re-check the tables
        // here before indexing.
        if (empty($fields[$field_id]['table']) && !$fields_updated) {
          unset($db_info['field_tables'][$field_id]);
          $this->fieldsUpdated($index);
          $fields_updated = TRUE;
          $fields = $db_info['field_tables'];
        }
        if (empty($fields[$field_id]['table']) && empty($field_errors[$field_id])) {
          // Log an error, but only once per field. Since a superfluous field is
          // not too serious, we just index the rest of the item normally.
          $field_errors[$field_id] = TRUE;
          $this->getLogger()->warning("Unknown field @field: please check (and re-save) the index's fields settings.", ['@field' => $field_id]);
          continue;
        }

        $field_info = $fields[$field_id];
        $table = $field_info['table'];
        $column = $field_info['column'];

        $this->database->delete($table)
          ->condition('item_id', $item_id)
          ->execute();

        $type = $field->getType();
        $values = [];
        foreach ($field->getValues() as $field_value) {
          $converted_value = $this->convert($field_value, $type, $field->getOriginalType(), $index);

          // Don't add NULL values to the array of values. Also, adding an empty
          // array is, of course, a waste of time.
          if (isset($converted_value) && $converted_value !== []) {
            $values = array_merge($values, is_array($converted_value) ? $converted_value : [$converted_value]);
          }
        }

        if (!$values) {
          // SQLite sometimes has problems letting columns not present in an
          // INSERT statement default to NULL, so we set NULL values for the
          // denormalized table explicitly.
          $denormalized_values[$column] = NULL;
          continue;
        }

        // If the field contains more than one value, we remember that the field
        // can be multi-valued.
        if (count($values) > 1) {
          $db_info['field_tables'][$field_id]['multi-valued'] = TRUE;
        }

        if ($this->getDataTypeHelper()->isTextType($type)) {
          // Remember the text table the first time we encounter it.
          if (!isset($text_table)) {
            $text_table = $table;
          }

          $unique_tokens = [];
          $denormalized_value = '';
          /** @var \Drupal\search_api\Plugin\search_api\data_type\value\TextTokenInterface $token */
          foreach ($values as $token) {
            $word = $token->getText();
            $score = $token->getBoost() * $item->getBoost();

            // In rare cases, tokens with leading or trailing whitespace can
            // slip through. Since this can lead to errors when such tokens are
            // part of a primary key (as in this case), we trim such whitespace
            // here.
            $word = trim($word);

            // Store the first 30 characters of the string as the denormalized
            // value.
            if (mb_strlen($denormalized_value) < 30) {
              $denormalized_value .= $word . ' ';
            }

            // Skip words that are too short, except for numbers.
            if (is_numeric($word)) {
              $word = ltrim($word, '-0');
            }
            elseif (mb_strlen($word) < $this->configuration['min_chars']) {
              continue;
            }

            // Taken from core search to reflect less importance of words later
            // in the text.
            // Focus is a decaying value in terms of the amount of unique words
            // up to this point. From 100 words and more, it decays, to (for
            // example) 0.5 at 500 words and 0.3 at 1000 words.
            $score *= min(1, .01 + 3.5 / (2 + count($unique_tokens) * .015));

            // Only insert each canonical base form of a word once.
            $word_base_form = $this->dbmsCompatibility->preprocessIndexValue($word);

            if (!isset($unique_tokens[$word_base_form])) {
              $unique_tokens[$word_base_form] = [
                'value' => $word,
                'score' => $score,
              ];
            }
            else {
              $unique_tokens[$word_base_form]['score'] += $score;
            }
          }
          $denormalized_values[$column] = mb_substr(trim($denormalized_value), 0, 30);
          if ($unique_tokens) {
            $field_name = static::getTextFieldName($field_id);
            $boost = $field_info['boost'];
            foreach ($unique_tokens as $token) {
              $score = $token['score'] * $boost * self::SCORE_MULTIPLIER;
              $score = round($score);
              // Take care that the score doesn't exceed the maximum value for
              // the database column (2^32-1).
              $score = min((int) $score, 4294967295);
              $text_inserts[] = [
                'item_id' => $item_id,
                'field_name' => $field_name,
                'word' => $token['value'],
                'score' => $score,
              ];
            }
          }
        }
        else {
          $denormalized_values[$column] = reset($values);

          // Make sure no duplicate values are inserted (which would lead to a
          // database exception).
          // Use the canonical base form of the value for the comparison to
          // avoid not catching different values that are duplicates under the
          // database table's collation.
          $case_insensitive_unique_values = [];
          foreach ($values as $value) {
            $value_base_form = $this->dbmsCompatibility->preprocessIndexValue("$value", 'field');
            // We still insert the value in its original case.
            $case_insensitive_unique_values[$value_base_form] = $value;
          }
          $values = array_values($case_insensitive_unique_values);

          $insert = $this->database->insert($table)
            ->fields(['item_id', 'value']);
          foreach ($values as $value) {
            $insert->values([
              'item_id' => $item_id,
              'value' => $value,
            ]);
          }
          $insert->execute();
        }
      }

      $this->database->insert($denormalized_table)
        ->fields(array_merge($denormalized_values, ['item_id' => $item_id]))
        ->execute();
      if ($text_inserts && isset($text_table)) {
        $query = $this->database->insert($text_table)
          ->fields(['item_id', 'field_name', 'word', 'score']);
        foreach ($text_inserts as $row) {
          $query->values($row);
        }
        $query->execute();
      }

      // In case any new fields were detected as multi-valued, we re-save the
      // index's DB info.
      $this->getKeyValueStore()->set($index->id(), $db_info);
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * Trims long field names to fit into the text table's field_name column.
   *
   * @param string $name
   *   The field name.
   *
   * @return string
   *   The field name as stored in the field_name column.
   */
  protected static function getTextFieldName($name) {
    if (strlen($name) > 191) {
      // Replace long field names with something unique and predictable.
      return Crypt::hashBase64($name);
    }
    else {
      return $name;
    }
  }

  /**
   * Converts a value between two search types.
   *
   * @param mixed $value
   *   The value to convert.
   * @param string $type
   *   The Search API type to convert to. (Has to be a type supported by this
   *   backend.)
   * @param string $original_type
   *   The value's original type.
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which this conversion takes place.
   *
   * @return mixed
   *   The converted value.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if $type is unknown.
   */
  protected function convert($value, $type, $original_type, IndexInterface $index) {
    if (!isset($value)) {
      // For text fields, we have to return an array even if the value is NULL.
      return $this->getDataTypeHelper()->isTextType($type) ? [] : NULL;
    }
    switch ($type) {
      case 'text':
        /** @var \Drupal\search_api\Plugin\search_api\data_type\value\TextValueInterface $value */
        $tokens = $value->getTokens();
        if ($tokens === NULL) {
          $tokens = [];
          $text = $value->getText();
          // For dates, splitting the timestamp makes no sense.
          if ($original_type == 'date') {
            $text = $this->getDateFormatter()
              ->format($text, 'custom', 'Y y F M n m j d l D');
          }
          foreach (static::splitIntoWords($text) as $word) {
            if ($word) {
              if (mb_strlen($word) > 50) {
                $this->getLogger()->warning('An overlong word (more than 50 characters) was encountered while indexing: %word.<br />Since database search servers currently cannot index words of more than 50 characters, the word was truncated for indexing. If this should not be a single word, please make sure the "Tokenizer" processor is enabled and configured correctly for index %index.', ['%word' => $word, '%index' => $index->label()]);
                $word = mb_substr($word, 0, 50);
              }
              $tokens[] = new TextToken($word);
            }
          }
        }
        else {
          while (TRUE) {
            foreach ($tokens as $i => $token) {
              // Check for over-long tokens.
              $score = $token->getBoost();
              $word = $token->getText();
              if (mb_strlen($word) > 50) {
                $new_tokens = [];
                foreach (static::splitIntoWords($word) as $word) {
                  if (mb_strlen($word) > 50) {
                    $this->getLogger()->warning('An overlong word (more than 50 characters) was encountered while indexing: %word.<br />Since database search servers currently cannot index words of more than 50 characters, the word was truncated for indexing. If this should not be a single word, please make sure the "Tokenizer" processor is enabled and configured correctly for index %index.', ['%word' => $word, '%index' => $index->label()]);
                    $word = mb_substr($word, 0, 50);
                  }
                  $new_tokens[] = new TextToken($word, $score);
                }
                array_splice($tokens, $i, 1, $new_tokens);
                // Restart the loop looking through all the tokens.
                continue 2;
              }
            }
            break;
          }
        }
        return $tokens;

      case 'string':
        // For non-dates, PHP can handle this well enough.
        if ($original_type == 'date') {
          return date('c', $value);
        }
        if (mb_strlen($value) > 255) {
          $value = mb_substr($value, 0, 255);
          $this->getLogger()->warning('An overlong value (more than 255 characters) was encountered while indexing: %value.<br />Database search servers currently cannot index such values correctly – the value was therefore trimmed to the allowed length.', ['%value' => $value]);
        }
        return $value;

      case 'integer':
      case 'date':
        return (int) $value;

      case 'decimal':
        return (float) $value;

      case 'boolean':
        return $value ? 1 : 0;

      default:
        throw new SearchApiException("Unknown field type '$type'.");
    }
  }

  /**
   * Splits the given string into words.
   *
   * Word characters as seen by this method are only alphanumerics.
   *
   * @param string $text
   *   The string to split.
   *
   * @return string[]
   *   All groups of alphanumeric characters contained in the string.
   */
  protected static function splitIntoWords($text) {
    return preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $item_ids) {
    try {
      $db_info = $this->getIndexDbInfo($index);

      if (empty($db_info['field_tables'])) {
        return;
      }
      foreach ($db_info['field_tables'] as $field) {
        $this->database->delete($field['table'])
          ->condition('item_id', $item_ids, 'IN')
          ->execute();
      }
      // Delete the denormalized field data.
      $this->database->delete($db_info['index_table'])
        ->condition('item_id', $item_ids, 'IN')
        ->execute();
    }
    catch (\Exception $e) {
      // The database operations might throw PDO or other exceptions, so we
      // catch them all and re-wrap them appropriately.
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL) {
    try {
      $db_info = $this->getIndexDbInfo($index);
      $datasource_field = $db_info['field_tables']['search_api_datasource']['column'];

      foreach ($db_info['field_tables'] as $field_id => $field) {
        if (!$datasource_id) {
          $this->database->truncate($field['table'])->execute();
          unset($db_info['field_tables'][$field_id]['multi-valued']);
        }
        else {
          if (!isset($query)) {
            $query = $this->database->select($db_info['index_table'], 't')
              ->fields('t', ['item_id'])
              ->condition($datasource_field, $datasource_id);
          }
          $this->database->delete($field['table'])
            ->condition('item_id', clone $query, 'IN')
            ->execute();
        }
      }

      if (!$datasource_id) {
        $this->getKeyValueStore()->set($index->id(), $db_info);
        $this->database->truncate($db_info['index_table'])->execute();
      }
      else {
        $this->database->delete($db_info['index_table'])
          ->condition($datasource_field, $datasource_id)
          ->execute();
      }
    }
    catch (\Exception $e) {
      // The database operations might throw PDO or other exceptions, so we
      // catch them all and re-wrap them appropriately.
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query) {
    $this->ignored = $this->warnings = [];
    $index = $query->getIndex();
    $db_info = $this->getIndexDbInfo($index);

    if (!isset($db_info['field_tables'])) {
      $index_id = $index->id();
      throw new SearchApiException("No field settings saved for index with ID '$index_id'.");
    }
    $fields = $this->getFieldInfo($index);
    $fields['search_api_id'] = [
      'column' => 'item_id',
    ];

    $db_query = $this->createDbQuery($query, $fields);

    $results = $query->getResults();

    $skip_count = $query->getOption('skip result count');
    $count = NULL;
    if (!$skip_count) {
      $count_query = $db_query->countQuery();
      $count = $count_query->execute()->fetchField();
      $results->setResultCount($count);
    }

    // With a "min_count" of 0, some facets can even be available if there are
    // no results.
    if ($query->getOption('search_api_facets')) {
      $facets = $this->getFacets($query, clone $db_query, $count);
      $results->setExtraData('search_api_facets', $facets);
    }
    // Everything else can be skipped if the count is 0.
    if ($skip_count || $count) {
      $query_options = $query->getOptions();
      if (isset($query_options['offset']) || isset($query_options['limit'])) {
        $offset = isset($query_options['offset']) ? $query_options['offset'] : 0;
        $limit = isset($query_options['limit']) ? $query_options['limit'] : 1000000;
        $db_query->range($offset, $limit);
      }

      $this->setQuerySort($query, $db_query, $fields);

      $result = $db_query->execute();

      foreach ($result as $row) {
        $item = $this->getFieldsHelper()->createItem($index, $row->item_id);
        $item->setScore($row->score / self::SCORE_MULTIPLIER);
        $results->addResultItem($item);
      }
      if ($skip_count && !empty($item)) {
        $results->setResultCount(1);
      }
    }

    // Add additional warnings and ignored keys.
    $metadata = [
      'warnings' => 'addWarning',
      'ignored' => 'addIgnoredSearchKey',
    ];
    foreach ($metadata as $property => $method) {
      foreach (array_keys($this->$property) as $value) {
        $results->$method($value);
      }
    }
  }

  /**
   * Creates a database query for a search.
   *
   * Used as a helper method in search() and getAutocompleteSuggestions().
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query for which to create the database query.
   * @param array $fields
   *   The internal field information to use.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A database query object which will return the appropriate results (except
   *   for the range and sorting) for the given search query.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if some illegal query setting (unknown field, etc.) was
   *   encountered.
   */
  protected function createDbQuery(QueryInterface $query, array $fields) {
    $keys = &$query->getKeys();
    $keys_set = (boolean) $keys;
    $keys = $this->prepareKeys($keys);

    // Only filter by fulltext keys if there are any real keys present.
    if ($keys && (!is_array($keys) || count($keys) > 2 || (!isset($keys['#negation']) && count($keys) > 1))) {
      // Special case: if the outermost $keys array has "#negation" set, we
      // can't handle it like other negated subkeys. To avoid additional
      // complexity later, we just wrap $keys so it becomes a subkey.
      if (!empty($keys['#negation'])) {
        $keys = [
          '#conjunction' => 'AND',
          $keys,
        ];
      }

      $fulltext_fields = $this->getQueryFulltextFields($query);
      if ($fulltext_fields) {
        $fulltext_field_information = [];
        foreach ($fulltext_fields as $name) {
          if (!isset($fields[$name])) {
            throw new SearchApiException("Unknown field '$name' specified as search target.");
          }
          if (!$this->getDataTypeHelper()->isTextType($fields[$name]['type'])) {
            $types = $this->getDataTypePluginManager()->getInstances();
            $type = $types[$fields[$name]['type']]->label();
            throw new SearchApiException("Cannot perform fulltext search on field '$name' of type '$type'.");
          }
          $fulltext_field_information[$name] = $fields[$name];
        }

        $db_query = $this->createKeysQuery($keys, $fulltext_field_information, $fields, $query->getIndex());
      }
      else {
        $this->getLogger()->warning('Search keys are given but no fulltext fields are defined.');
        $msg = $this->t('Search keys are given but no fulltext fields are defined.');
        $this->warnings[(string) $msg] = 1;
      }
    }
    elseif ($keys_set) {
      $msg = $this->t('No valid search keys were present in the query.');
      $this->warnings[(string) $msg] = 1;
    }

    if (!isset($db_query)) {
      $db_info = $this->getIndexDbInfo($query->getIndex());
      $db_query = $this->database->select($db_info['index_table'], 't');
      $db_query->addField('t', 'item_id', 'item_id');
      $db_query->addExpression(':score', 'score', [':score' => self::SCORE_MULTIPLIER]);
      $db_query->distinct();
    }

    $condition_group = $query->getConditionGroup();
    $this->addLanguageConditions($condition_group, $query);
    if ($condition_group->getConditions()) {
      $condition = $this->createDbCondition($condition_group, $fields, $db_query, $query->getIndex());
      if ($condition) {
        $db_query->condition($condition);
      }
    }

    $db_query->addTag('search_api_db_search');
    $db_query->addMetaData('search_api_query', $query);
    $db_query->addMetaData('search_api_db_fields', $fields);

    // Allow subclasses and other modules to alter the query (before a count
    // query is constructed from it).
    $this->getModuleHandler()->alter('search_api_db_query', $db_query, $query);
    $this->preQuery($db_query, $query);

    return $db_query;
  }

  /**
   * Removes nested expressions and phrase groupings from the search keys.
   *
   * Used as a helper method in createDbQuery() and createDbCondition().
   *
   * @param array|string|null $keys
   *   The keys which should be preprocessed.
   *
   * @return array|string|null
   *   The preprocessed keys.
   */
  protected function prepareKeys($keys) {
    if (is_scalar($keys)) {
      $keys = $this->splitKeys($keys);
      return is_array($keys) ? $this->eliminateDuplicates($keys) : $keys;
    }
    elseif (!$keys) {
      return NULL;
    }
    $keys = $this->eliminateDuplicates($this->splitKeys($keys));
    $conj = $keys['#conjunction'];
    $neg = !empty($keys['#negation']);
    foreach ($keys as $i => &$nested) {
      if (is_array($nested)) {
        $nested = $this->prepareKeys($nested);
        if (is_array($nested) && $neg == !empty($nested['#negation'])) {
          if ($nested['#conjunction'] == $conj) {
            unset($nested['#conjunction'], $nested['#negation']);
            foreach ($nested as $renested) {
              $keys[] = $renested;
            }
            unset($keys[$i]);
          }
        }
      }
    }
    $keys = array_filter($keys);
    if (($count = count($keys)) <= 2) {
      if ($count < 2 || isset($keys['#negation'])) {
        $keys = NULL;
      }
      else {
        unset($keys['#conjunction']);
        $keys = reset($keys);
      }
    }
    return $keys;
  }

  /**
   * Splits a keyword expression into separate words.
   *
   * Used as a helper method in prepareKeys().
   *
   * @param array|string $keys
   *   The keys to split.
   *
   * @return array|string|null
   *   The keys split into separate words.
   */
  protected function splitKeys($keys) {
    if (is_scalar($keys)) {
      $processed_keys = $this->dbmsCompatibility->preprocessIndexValue(trim($keys));
      if (is_numeric($processed_keys)) {
        return ltrim($processed_keys, '-0');
      }
      elseif (mb_strlen($processed_keys) < $this->configuration['min_chars']) {
        $this->ignored[$keys] = 1;
        return NULL;
      }
      $words = static::splitIntoWords($processed_keys);
      if (count($words) > 1) {
        $processed_keys = $this->splitKeys($words);
        if ($processed_keys) {
          $processed_keys['#conjunction'] = 'AND';
        }
        else {
          $processed_keys = NULL;
        }
      }
      return $processed_keys;
    }
    foreach ($keys as $i => $key) {
      if (Element::child($i)) {
        $keys[$i] = $this->splitKeys($key);
      }
    }
    return array_filter($keys);
  }

  /**
   * Eliminates duplicate keys from a keyword array.
   *
   * Used as a helper method in prepareKeys().
   *
   * @param array $keys
   *   The keywords to parse.
   * @param array $words
   *   (optional) A cache of all encountered words so far. Used internally for
   *   recursive invocations.
   *
   * @return array
   *   The processed keywords.
   */
  protected function eliminateDuplicates(array $keys, array &$words = []) {
    foreach ($keys as $i => $word) {
      if (!Element::child($i)) {
        continue;
      }
      if (is_scalar($word)) {
        if (isset($words[$word])) {
          unset($keys[$i]);
        }
        else {
          $words[$word] = TRUE;
        }
      }
      else {
        $keys[$i] = $this->eliminateDuplicates($word, $words);
      }
    }
    return $keys;
  }

  /**
   * Creates a SELECT query for given search keys.
   *
   * Used as a helper method in createDbQuery() and createDbCondition().
   *
   * @param string|array $keys
   *   The search keys, formatted like the return value of
   *   \Drupal\search_api\ParseMode\ParseModeInterface::parseInput(), but
   *   preprocessed according to internal requirements.
   * @param array $fields
   *   The fulltext fields on which to search, with their names as keys mapped
   *   to internal information about them.
   * @param array $all_fields
   *   Internal information about all indexed fields on the index.
   * @param \Drupal\search_api\IndexInterface $index
   *   The index we're searching on.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A SELECT query returning item_id and score (or only item_id, if
   *   $keys['#negation'] is set).
   */
  protected function createKeysQuery($keys, array $fields, array $all_fields, IndexInterface $index) {
    if (!is_array($keys)) {
      $keys = [
        '#conjunction' => 'AND',
        $keys,
      ];
    }

    $neg = !empty($keys['#negation']);
    $conj = $keys['#conjunction'];
    $words = [];
    $nested = [];
    $negated = [];
    $db_query = NULL;
    $mul_words = FALSE;
    $neg_nested = $neg && $conj == 'AND';
    $match_parts = $this->configuration['matching'] !== 'words';
    $keyword_hits = [];
    $prefix_search = $this->configuration['matching'] === 'prefix';

    foreach ($keys as $i => $key) {
      if (!Element::child($i)) {
        continue;
      }
      if (is_scalar($key)) {
        $words[] = $key;
      }
      elseif (empty($key['#negation'])) {
        if ($neg) {
          // If this query is negated, we also only need item IDs from
          // subqueries.
          $key['#negation'] = TRUE;
        }
        $nested[] = $key;
      }
      else {
        $negated[] = $key;
      }
    }
    $word_count = count($words);
    $subs = $word_count + count($nested);
    $not_nested = ($subs <= 1 && count($fields) == 1) || ($neg && $conj == 'OR' && !$negated);

    if ($words) {
      // All text fields in the index share a table. Get name from the first.
      $field = reset($fields);
      $db_query = $this->database->select($field['table'], 't');
      $mul_words = ($word_count > 1);
      if ($neg_nested) {
        $db_query->fields('t', ['item_id', 'word']);
      }
      elseif ($neg) {
        $db_query->fields('t', ['item_id']);
      }
      elseif ($not_nested) {
        $db_query->fields('t', ['item_id', 'score']);
      }
      else {
        $db_query->fields('t', ['item_id', 'score', 'word']);
      }

      if (!$match_parts) {
        $db_query->condition('word', $words, 'IN');
      }
      else {
        $db_or = new Condition('OR');
        // GROUP BY all existing non-grouped, non-aggregated columns – except
        // "word", which we remove since it will be useless to us in this case.
        $columns = &$db_query->getFields();
        unset($columns['word']);
        foreach (array_keys($columns) as $column) {
          $db_query->groupBy($column);
        }

        foreach ($words as $i => $word) {
          $like = $this->database->escapeLike($word);
          $like = $prefix_search ? "$like%" : "%$like%";
          $db_or->condition('t.word', $like, 'LIKE');

          // Add an expression for each keyword that shows whether the indexed
          // word matches that particular keyword. That way we don't return a
          // result multiple times if a single indexed word (partially) matches
          // multiple keywords. We also remember the column name so we can
          // afterwards verify that each word matched at least once.
          $alias = 'w' . $i;
          $like = '%' . $this->database->escapeLike($word) . '%';
          $alias = $db_query->addExpression("CASE WHEN t.word LIKE :like_$alias THEN 1 ELSE 0 END", $alias, [":like_$alias" => $like]);
          $db_query->groupBy($alias);
          $keyword_hits[] = $alias;
        }
        // Also add expressions for any nested queries.
        for ($i = $word_count; $i < $subs; ++$i) {
          $alias = 'w' . $i;
          $alias = $db_query->addExpression('0', $alias);
          $db_query->groupBy($alias);
          $keyword_hits[] = $alias;
        }
        $db_query->condition($db_or);
      }

      $db_query->condition('field_name', array_map([__CLASS__, 'getTextFieldName'], array_keys($fields)), 'IN');
    }

    if ($nested) {
      $word = '';
      foreach ($nested as $i => $k) {
        $query = $this->createKeysQuery($k, $fields, $all_fields, $index);
        if (!$neg) {
          if (!$match_parts) {
            $word .= ' ';
            $var = ':word' . strlen($word);
            $query->addExpression($var, 'word', [$var => $word]);
          }
          else {
            $i += $word_count;
            for ($j = 0; $j < $subs; ++$j) {
              $alias = isset($keyword_hits[$j]) ? $keyword_hits[$j] : "w$j";
              $keyword_hits[$j] = $query->addExpression($i == $j ? '1' : '0', $alias);
            }
          }
        }
        if (!isset($db_query)) {
          $db_query = $query;
        }
        elseif ($not_nested) {
          $db_query->union($query, 'UNION');
        }
        else {
          $db_query->union($query, 'UNION ALL');
        }
      }
    }

    if (isset($db_query) && !$not_nested) {
      $db_query = $this->database->select($db_query, 't');
      $db_query->addField('t', 'item_id', 'item_id');
      if (!$neg) {
        $db_query->addExpression('SUM(t.score)', 'score');
        $db_query->groupBy('t.item_id');
      }
      if ($conj == 'AND' && $subs > 1) {
        $var = ':subs' . ((int) $subs);
        if (!$db_query->getGroupBy()) {
          $db_query->groupBy('t.item_id');
        }
        if (!$match_parts) {
          if ($mul_words) {
            $db_query->having('COUNT(DISTINCT t.word) >= ' . $var, [$var => $subs]);
          }
          else {
            $db_query->having('COUNT(t.word) >= ' . $var, [$var => $subs]);
          }
        }
        else {
          foreach ($keyword_hits as $alias) {
            $db_query->having("SUM($alias) >= 1");
          }
        }
      }
    }

    if ($negated) {
      if (!isset($db_query) || $conj == 'OR') {
        if (isset($db_query)) {
          // We are in a rather bizarre case where the keys are something like
          // "a OR (NOT b)".
          $old_query = $db_query;
        }

        // We use this table because all items should be contained exactly once.
        $db_info = $this->getIndexDbInfo($index);
        $db_query = $this->database->select($db_info['index_table'], 't');
        $db_query->addField('t', 'item_id', 'item_id');
        if (!$neg) {
          $db_query->addExpression(':score', 'score', [':score' => self::SCORE_MULTIPLIER]);
          $db_query->distinct();
        }
      }

      if ($conj == 'AND') {
        foreach ($negated as $k) {
          $db_query->condition('t.item_id', $this->createKeysQuery($k, $fields, $all_fields, $index), 'NOT IN');
        }
      }
      else {
        $or = new Condition('OR');
        foreach ($negated as $k) {
          $or->condition('t.item_id', $this->createKeysQuery($k, $fields, $all_fields, $index), 'NOT IN');
        }
        if (isset($old_query)) {
          $or->condition('t.item_id', $old_query, 'NOT IN');
        }
        $db_query->condition($or);
      }
    }

    if ($neg_nested) {
      $db_query = $this->database->select($db_query, 't')->fields('t', ['item_id']);
    }

    return $db_query;
  }

  /**
   * Adds item language conditions to the condition group, if applicable.
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $condition_group
   *   The condition group on which to set conditions.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to inspect for language settings.
   *
   * @see \Drupal\search_api\Query\QueryInterface::getLanguages()
   */
  protected function addLanguageConditions(ConditionGroupInterface $condition_group, QueryInterface $query) {
    $languages = $query->getLanguages();
    if ($languages !== NULL) {
      $condition_group->addCondition('search_api_language', $languages, 'IN');
    }
  }

  /**
   * Creates a database query condition for a given search filter.
   *
   * Used as a helper method in createDbQuery().
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $conditions
   *   The conditions for which a condition should be created.
   * @param array $fields
   *   Internal information about the index's fields.
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   The database query to which the condition will be added.
   * @param \Drupal\search_api\IndexInterface $index
   *   The index we're searching on.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface|null
   *   The condition to set on the query, or NULL if none is necessary.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an unknown field or operator was used in one of the contained
   *   conditions.
   */
  protected function createDbCondition(ConditionGroupInterface $conditions, array $fields, SelectInterface $db_query, IndexInterface $index) {
    $conjunction = $conditions->getConjunction();
    $db_condition = new Condition($conjunction);
    $db_info = $this->getIndexDbInfo($index);

    // Store the table aliases for the fields in this condition group.
    $tables = [];
    $wildcard_count = 0;
    foreach ($conditions->getConditions() as $condition) {
      if ($condition instanceof ConditionGroupInterface) {
        $sub_condition = $this->createDbCondition($condition, $fields, $db_query, $index);
        if ($sub_condition) {
          $db_condition->condition($sub_condition);
        }
      }
      else {
        $field = $condition->getField();
        $operator = $condition->getOperator();
        $value = $condition->getValue();
        $this->validateOperator($operator);
        $not_equals_operators = ['<>', 'NOT IN', 'NOT BETWEEN'];
        $not_equals = in_array($operator, $not_equals_operators);
        $not_between = $operator == 'NOT BETWEEN';

        if (!isset($fields[$field])) {
          throw new SearchApiException("Unknown field in filter clause: '$field'.");
        }
        $field_info = $fields[$field];
        // For NULL values, we can just use the single-values table, since we
        // only need to know if there's any value at all for that field.
        if ($value === NULL || empty($field_info['multi-valued'])) {
          if (empty($tables[NULL])) {
            $table = ['table' => $db_info['index_table']];
            $tables[NULL] = $this->getTableAlias($table, $db_query);
          }
          $column = $tables[NULL] . '.' . $field_info['column'];
          if ($value === NULL) {
            $method = $not_equals ? 'isNotNull' : 'isNull';
            $db_condition->$method($column);
          }
          elseif ($not_between) {
            $nested_condition = new Condition('OR');
            $nested_condition->condition($column, $value[0], '<');
            $nested_condition->condition($column, $value[1], '>');
            $nested_condition->isNull($column);
            $db_condition->condition($nested_condition);
          }
          elseif ($not_equals) {
            // Since SQL never returns TRUE for comparison with NULL values, we
            // need to include "OR field IS NULL" explicitly for some operators.
            $nested_condition = new Condition('OR');
            $nested_condition->condition($column, $value, $operator);
            $nested_condition->isNull($column);
            $db_condition->condition($nested_condition);
          }
          else {
            $db_condition->condition($column, $value, $operator);
          }
        }
        elseif ($this->getDataTypeHelper()->isTextType($field_info['type'])) {
          $keys = $this->prepareKeys($value);
          if (!isset($keys)) {
            continue;
          }
          $query = $this->createKeysQuery($keys, [$field => $field_info], $fields, $index);
          // We only want the item IDs, so we use the keys query as a nested
          // query.
          $query = $this->database->select($query, 't')
            ->fields('t', ['item_id']);
          $db_condition->condition('t.item_id', $query, $not_equals ? 'NOT IN' : 'IN');
        }
        elseif ($not_equals) {
          // The situation is more complicated for negative conditions on
          // multi-valued fields, since we must make sure that results are
          // excluded if ANY of the field's values equals the one(s) given in
          // this condition. Probably the most performant way to do this is to
          // do a LEFT JOIN with a positive filter on the excluded values in the
          // ON clause and then make sure we have no value for the field.
          if ($not_between) {
            $wildcard1 = ':values_' . ++$wildcard_count;
            $wildcard2 = ':values_' . ++$wildcard_count;
            $arguments = array_combine([$wildcard1, $wildcard2], $value);
            $additional_on = "%alias.value BETWEEN $wildcard1 AND $wildcard2";
          }
          else {
            $wildcard = ':values_' . ++$wildcard_count . '[]';
            $arguments = [$wildcard => (array) $value];
            $additional_on = "%alias.value IN ($wildcard)";
          }
          $alias = $this->getTableAlias($field_info, $db_query, TRUE, 'leftJoin', $additional_on, $arguments);
          $db_condition->isNull($alias . '.value');
        }
        else {
          // We need to join the table if it hasn't been joined (for this
          // condition group) before, or if we have "AND" as the active
          // conjunction.
          if ($conjunction == 'AND' || empty($tables[$field])) {
            $tables[$field] = $this->getTableAlias($field_info, $db_query, TRUE);
          }
          $column = $tables[$field] . '.value';
          $db_condition->condition($column, $value, $operator);
        }
      }
    }
    return $db_condition->count() ? $db_condition : NULL;
  }

  /**
   * Joins a field's table into a database select query.
   *
   * @param array $field
   *   The field information array. The "table" key should contain the table
   *   name to which a join should be made.
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   The database query used.
   * @param bool $new_join
   *   (optional) If TRUE, a join is done even if the table was already joined
   *   to in the query.
   * @param string $join
   *   (optional) The join method to use. Must be a method of the $db_query.
   *   Normally, "join", "innerJoin", "leftJoin" and "rightJoin" are supported.
   * @param string|null $additional_on
   *   (optional) If given, an SQL string with additional conditions for the ON
   *   clause of the join.
   * @param array $on_arguments
   *   (optional) Additional arguments for the ON clause.
   *
   * @return string
   *   The alias for the field's table.
   */
  protected function getTableAlias(array $field, SelectInterface $db_query, $new_join = FALSE, $join = 'leftJoin', $additional_on = NULL, array $on_arguments = []) {
    if (!$new_join) {
      foreach ($db_query->getTables() as $alias => $info) {
        $table = $info['table'];
        if (is_scalar($table) && $table == $field['table']) {
          return $alias;
        }
      }
    }
    $condition = 't.item_id = %alias.item_id';
    if ($additional_on) {
      $condition .= ' AND ' . $additional_on;
    }
    return $db_query->$join($field['table'], 't', $condition, $on_arguments);
  }

  /**
   * Preprocesses a search's database query before it is executed.
   *
   * This allows subclasses to alter the DB query before a count query (or facet
   * queries, or other related queries) are constructed from it.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   The database query to be executed for the search. Will have "item_id" and
   *   "score" columns in its result.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query that is being executed.
   *
   * @see hook_search_api_db_query_alter()
   */
  protected function preQuery(SelectInterface &$db_query, QueryInterface $query) {}

  /**
   * Adds the approiate "ORDER BY" statements to a search database query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query whose sorts should be applied.
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   The database query constructed for the search.
   * @param string[][] $fields
   *   An array containing information about the internal server storage of the
   *   indexed fields.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an illegal sort was specified.
   */
  protected function setQuerySort(QueryInterface $query, SelectInterface $db_query, array $fields) {
    $sort = $query->getSorts();
    if ($sort) {
      $db_fields = $db_query->getFields();
      foreach ($sort as $field_name => $order) {
        if ($order != QueryInterface::SORT_ASC && $order != QueryInterface::SORT_DESC) {
          $msg = $this->t('Unknown sort order @order. Assuming "@default".', [
            '@order' => $order,
            '@default' => QueryInterface::SORT_ASC,
          ]);
          $this->warnings[(string) $msg] = 1;
          $order = QueryInterface::SORT_ASC;
        }
        if ($field_name == 'search_api_relevance') {
          $db_query->orderBy('score', $order);
          continue;
        }

        if (!isset($fields[$field_name])) {
          throw new SearchApiException("Trying to sort on unknown field '$field_name'.");
        }
        $index_table = $this->getIndexDbInfo($query->getIndex())['index_table'];
        $alias = $this->getTableAlias(['table' => $index_table], $db_query);
        $db_query->orderBy($alias . '.' . $fields[$field_name]['column'], $order);
        // PostgreSQL automatically adds a field to the SELECT list when
        // sorting on it. Therefore, if we have aggregations present we also
        // have to add the field to the GROUP BY (since Drupal won't do it for
        // us). However, if no aggregations are present, a GROUP BY would lead
        // to another error. Therefore, we only add it if there is already a
        // GROUP BY.
        if ($db_query->getGroupBy()) {
          $db_query->groupBy($alias . '.' . $fields[$field_name]['column']);
        }
        // For SELECT DISTINCT queries in combination with an ORDER BY clause,
        // MySQL 5.7 and higher require that the ORDER BY expressions are part
        // of the field list. Ensure that all fields used for sorting are part
        // of the select list.
        if (empty($db_fields[$fields[$field_name]['column']])) {
          $db_query->addField($alias, $fields[$field_name]['column']);
        }
      }
    }
    else {
      $db_query->orderBy('score', 'DESC');
    }
  }

  /**
   * Computes facets for a search query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query for which facets should be computed.
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   A database select query which returns all results of that search query.
   * @param int|null $result_count
   *   (optional) The total number of results of the search query, if known.
   *
   * @return array
   *   An array of facets, as specified by the search_api_facets feature.
   */
  protected function getFacets(QueryInterface $query, SelectInterface $db_query, $result_count = NULL) {
    $fields = $this->getFieldInfo($query->getIndex());
    $ret = [];
    foreach ($query->getOption('search_api_facets') as $key => $facet) {
      if (empty($fields[$facet['field']])) {
        $msg = $this->t('Unknown facet field @field.', ['@field' => $facet['field']]);
        $this->warnings[(string) $msg] = 1;
        continue;
      }
      $field = $fields[$facet['field']];

      if (empty($facet['operator']) || $facet['operator'] != 'or') {
        // First, check whether this can even possibly have any results.
        if ($result_count !== NULL && $result_count < $facet['min_count']) {
          continue;
        }

        // All the AND facets can use the main query. If we didn't yet create a
        // temporary table for them yet, do so now.
        if (!isset($table)) {
          $table = $this->getTemporaryResultsTable($db_query);
        }
        if ($table) {
          $select = $this->database->select($table, 't');
        }
        else {
          // If no temporary table could be created (most likely due to a
          // missing permission), use a nested query instead.
          $select = $this->database->select(clone $db_query, 't');
        }
        // In case we didn't get the result count passed to the method, we can
        // get it now. (This allows us to skip AND facets with a min_count
        // higher than the result count.)
        if ($result_count === NULL) {
          $result_count = $select->countQuery()->execute()->fetchField();
          if ($result_count < $facet['min_count']) {
            continue;
          }
        }
      }
      else {
        // For OR facets, we need to build a different base query that excludes
        // the facet filters applied to the facet.
        $or_query = clone $query;
        $conditions = &$or_query->getConditionGroup()->getConditions();
        $tag = 'facet:' . $facet['field'];
        foreach ($conditions as $i => $condition) {
          if ($condition instanceof ConditionGroupInterface && $condition->hasTag($tag)) {
            unset($conditions[$i]);
          }
        }
        try {
          $or_db_query = $this->createDbQuery($or_query, $fields);
        }
        catch (SearchApiException $e) {
          $this->logException($e, '%type while trying to create a facets query: @message in %function (line %line of %file).');
          continue;
        }
        $select = $this->database->select($or_db_query, 't');
      }

      // If "Include missing facet" is disabled, we use an INNER JOIN and add IS
      // NOT NULL for shared tables.
      $is_text_type = $this->getDataTypeHelper()->isTextType($field['type']);
      $alias = $this->getTableAlias($field, $select, TRUE, $facet['missing'] ? 'leftJoin' : 'innerJoin');
      $select->addField($alias, $is_text_type ? 'word' : 'value', 'value');
      if ($is_text_type) {
        $select->condition($alias . '.field_name', $this->getTextFieldName($facet['field']));
      }
      if (!$facet['missing'] && !$is_text_type) {
        $select->isNotNull($alias . '.value');
      }
      $select->addExpression('COUNT(DISTINCT t.item_id)', 'num');
      $select->groupBy('value');
      $select->orderBy('num', 'DESC');
      $select->orderBy('value', 'ASC');

      $limit = $facet['limit'];
      if ((int) $limit > 0) {
        $select->range(0, $limit);
      }
      if ($facet['min_count'] > 1) {
        $select->having('COUNT(DISTINCT t.item_id) >= :count', [':count' => $facet['min_count']]);
      }

      $terms = [];
      $values = [];
      $has_missing = FALSE;
      foreach ($select->execute() as $row) {
        $terms[] = [
          'count' => $row->num,
          'filter' => isset($row->value) ? '"' . $row->value . '"' : '!',
        ];
        if (isset($row->value)) {
          $values[] = $row->value;
        }
        else {
          $has_missing = TRUE;
        }
      }

      // If 'Minimum facet count' is set to 0 in the display options for this
      // facet, we need to retrieve all facets, even ones that aren't matched in
      // our search result set above. Here we SELECT all DISTINCT facets, and
      // add in those facets that weren't added above.
      if ($facet['min_count'] < 1) {
        $select = $this->database->select($field['table'], 't');
        $select->addField('t', 'value', 'value');
        $select->distinct();
        if ($values) {
          $select->condition('value', $values, 'NOT IN');
        }
        $select->isNotNull('value');
        foreach ($select->execute() as $row) {
          $terms[] = [
            'count' => 0,
            'filter' => '"' . $row->value . '"',
          ];
        }
        if ($facet['missing'] && !$has_missing) {
          $terms[] = [
            'count' => 0,
            'filter' => '!',
          ];
        }
      }

      $ret[$key] = $terms;
    }
    return $ret;
  }

  /**
   * Creates a temporary table from a select query.
   *
   * Will return the name of a table containing the item IDs of all results, or
   * FALSE on failure.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   The select query whose results should be stored in the temporary table.
   *
   * @return string|false
   *   The name of the temporary table, or FALSE on failure.
   */
  protected function getTemporaryResultsTable(SelectInterface $db_query) {
    // We only need the id field, not the score.
    $fields = &$db_query->getFields();
    unset($fields['score']);
    if (count($fields) != 1 || !isset($fields['item_id'])) {
      $this->getLogger()->warning('Error while adding facets: only "item_id" field should be used, used are: @fields.', ['@fields' => implode(', ', array_keys($fields))]);
      return FALSE;
    }
    $expressions = &$db_query->getExpressions();
    $expressions = [];

    // Remove the ORDER BY clause, as it may refer to expressions that are
    // unset above.
    $orderBy = &$db_query->getOrderBy();
    $orderBy = [];

    // If there's a GROUP BY for item_id, we leave that, all others need to be
    // discarded.
    $group_by = &$db_query->getGroupBy();
    $group_by = array_intersect_key($group_by, ['t.item_id' => TRUE]);

    $db_query->distinct();
    if (!$db_query->preExecute()) {
      return FALSE;
    }
    $args = $db_query->getArguments();
    try {
      $result = $this->database->queryTemporary((string) $db_query, $args);
    }
    catch (\PDOException $e) {
      $this->logException($e, '%type while trying to create a temporary table: @message in %function (line %line of %file).');
      return FALSE;
    }
    catch (DatabaseException $e) {
      $this->logException($e, '%type while trying to create a temporary table: @message in %function (line %line of %file).');
      return FALSE;
    }
    return $result;
  }

  /**
   * Retrieves autocompletion suggestions for some user input.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   A query representing the base search, with all completely entered words
   *   in the user input so far as the search keys.
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   An object containing details about the search the user is on, and
   *   settings for the autocompletion. See the class documentation for details.
   *   Especially $search->getOptions() should be checked for settings, like
   *   whether to try and estimate result counts for returned suggestions.
   * @param string $incomplete_key
   *   The start of another fulltext keyword for the search, which should be
   *   completed. Might be empty, in which case all user input up to now was
   *   considered completed. Then, additional keywords for the search could be
   *   suggested.
   * @param string $user_input
   *   The complete user input for the fulltext search keywords so far.
   *
   * @return \Drupal\search_api_autocomplete\Suggestion\SuggestionInterface[]
   *   An array of autocomplete suggestions.
   *
   * @see \Drupal\search_api_autocomplete\AutocompleteBackendInterface::getAutocompleteSuggestions()
   */
  public function getAutocompleteSuggestions(QueryInterface $query, SearchInterface $search, $incomplete_key, $user_input) {
    $settings = $this->configuration['autocomplete'];

    // If none of the options is checked, the user apparently chose a very
    // roundabout way of telling us he doesn't want autocompletion.
    if (!array_filter($settings)) {
      return [];
    }

    $index = $query->getIndex();
    $db_info = $this->getIndexDbInfo($index);
    if (empty($db_info['field_tables'])) {
      return [];
    }
    $fields = $this->getFieldInfo($index);

    $suggestions = [];
    $factory = new SuggestionFactory($user_input);
    $passes = [];
    $incomplete_like = NULL;

    // Make the input lowercase as the indexed data is (usually) also all
    // lowercase.
    $incomplete_key = mb_strtolower($incomplete_key);
    $user_input = mb_strtolower($user_input);

    // Decide which methods we want to use.
    if ($incomplete_key && $settings['suggest_suffix']) {
      $passes[] = 1;
      $incomplete_like = $this->database->escapeLike($incomplete_key) . '%';
    }
    if ($settings['suggest_words'] && (!$incomplete_key || strlen($incomplete_key) >= $this->configuration['min_chars'])) {
      $passes[] = 2;
    }

    if (!$passes) {
      return [];
    }

    // We want about half of the suggestions from each enabled method.
    $limit = $query->getOption('limit', 10);
    $limit /= count($passes);
    $limit = ceil($limit);

    // Also collect all keywords already contained in the query so we don't
    // suggest them.
    $keys = static::splitIntoWords($user_input);
    $keys = array_combine($keys, $keys);

    foreach ($passes as $pass) {
      if ($pass == 2 && $incomplete_key) {
        $query->keys($user_input);
      }
      // To avoid suggesting incomplete words, we have to temporarily disable
      // partial matching. There should be no way we'll save the server during
      // the createDbQuery() call, so this should be safe.
      $configuration = $this->configuration;
      $db_query = NULL;
      try {
        $this->configuration['matching'] = 'words';
        $db_query = $this->createDbQuery($query, $fields);
        $this->configuration = $configuration;

        // We need a list of all current results to match the suggestions
        // against. However, since MySQL doesn't allow using a temporary table
        // multiple times in one query, we regrettably have to do it this way.
        $fulltext_fields = $this->getQueryFulltextFields($query);
        if (count($fulltext_fields) > 1) {
          $all_results = $db_query->execute()->fetchCol();
          // Compute the total number of results so we can later sort out
          // matches that occur too often.
          $total = count($all_results);
        }
        else {
          $table = $this->getTemporaryResultsTable($db_query);
          if (!$table) {
            return [];
          }
          $all_results = $this->database->select($table, 't')
            ->fields('t', ['item_id']);
          $sql = "SELECT COUNT(item_id) FROM {{$table}}";
          $total = $this->database->query($sql)->fetchField();
        }
      }
      catch (SearchApiException $e) {
        // If the exception was in createDbQuery(), we need to reset the
        // configuration here.
        $this->configuration = $configuration;
        $this->logException($e, '%type while trying to create autocomplete suggestions: @message in %function (line %line of %file).');
        continue;
      }
      $max_occurrences = $this->getConfigFactory()
        ->get('search_api_db.settings')
        ->get('autocomplete_max_occurrences');
      $max_occurrences = max(1, floor($total * $max_occurrences));

      if (!$total) {
        if ($pass == 1) {
          return [];
        }
        continue;
      }

      /** @var \Drupal\Core\Database\Query\SelectInterface|null $word_query */
      $word_query = NULL;
      foreach ($fulltext_fields as $field) {
        if (!isset($fields[$field]) || !$this->getDataTypeHelper()->isTextType($fields[$field]['type'])) {
          continue;
        }
        $field_query = $this->database->select($fields[$field]['table'], 't');
        $field_query->fields('t', ['word', 'item_id'])
          ->condition('field_name', $field)
          ->condition('item_id', $all_results, 'IN');
        if ($pass == 1) {
          $field_query->condition('word', $incomplete_like, 'LIKE')
            ->condition('word', $keys, 'NOT IN');
        }
        if (!isset($word_query)) {
          $word_query = $field_query;
        }
        else {
          $word_query->union($field_query);
        }
      }
      if (!$word_query) {
        return [];
      }
      $db_query = $this->database->select($word_query, 't');
      $db_query->addExpression('COUNT(DISTINCT item_id)', 'results');
      $db_query->fields('t', ['word'])
        ->groupBy('word')
        ->having('COUNT(DISTINCT item_id) <= :max', [':max' => $max_occurrences])
        ->orderBy('results', 'DESC')
        ->range(0, $limit);
      $incomp_len = strlen($incomplete_key);
      foreach ($db_query->execute() as $row) {
        $suffix = ($pass == 1) ? substr($row->word, $incomp_len) : ' ' . $row->word;
        $suggestions[] = $factory->createFromSuggestionSuffix($suffix, $row->results);
      }
    }

    return $suggestions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSpecialFields(IndexInterface $index, ItemInterface $item = NULL) {
    $fields = parent::getSpecialFields($index, $item);
    unset($fields['search_api_id']);
    return $fields;
  }

  /**
   * Retrieves the internal field information.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index whose fields should be retrieved.
   *
   * @return array[]
   *   An array of arrays. The outer array is keyed by field name. Each value
   *   is an associative array with information on the field.
   */
  protected function getFieldInfo(IndexInterface $index) {
    $db_info = $this->getIndexDbInfo($index);
    return $db_info['field_tables'];
  }

  /**
   * Retrieves the database info for the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   *
   * @return array
   *   The index data from the key-value store.
   */
  protected function getIndexDbInfo(IndexInterface $index) {
    $db_info = $this->getKeyValueStore()->get($index->id(), []);
    if ($db_info && $db_info['server'] != $this->server->id()) {
      return [];
    }
    return $db_info;
  }

  /**
   * Implements the magic __sleep() method.
   *
   * Prevents the database connection and logger from being serialized.
   */
  public function __sleep() {
    $properties = array_flip(parent::__sleep());
    unset($properties['database']);
    unset($properties['logger']);
    return array_keys($properties);
  }

  /**
   * Implements the magic __wakeup() method.
   *
   * Reloads the database connection and logger.
   */
  public function __wakeup() {
    parent::__wakeup();

    if (isset($this->configuration['database'])) {
      list($key, $target) = explode(':', $this->configuration['database'], 2);
      $this->database = CoreDatabase::getConnection($target, $key);
    }
  }

}
