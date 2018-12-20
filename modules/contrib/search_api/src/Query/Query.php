<?php

namespace Drupal\search_api\Query;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Display\DisplayPluginManagerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\ParseMode\ParseModeInterface;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\QueryHelperInterface;

/**
 * Provides a standard implementation for a Search API query.
 */
class Query implements QueryInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait {
    __sleep as traitSleep;
    __wakeup as traitWakeup;
  }

  /**
   * The index on which the query will be executed.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The index's ID.
   *
   * Used when serializing, to avoid serializing the index, too.
   *
   * @var string|null
   */
  protected $indexId;

  /**
   * The search results.
   *
   * @var \Drupal\search_api\Query\ResultSetInterface
   */
  protected $results;

  /**
   * The search ID set for this query.
   *
   * @var string
   */
  protected $searchId;

  /**
   * The parse mode to use for fulltext search keys.
   *
   * @var \Drupal\search_api\ParseMode\ParseModeInterface|null
   */
  protected $parseMode;

  /**
   * The processing level for this search query.
   *
   * One of the \Drupal\search_api\Query\QueryInterface::PROCESSING_* constants.
   *
   * @var int
   */
  protected $processingLevel = self::PROCESSING_FULL;

  /**
   * The language codes which should be searched by this query.
   *
   * @var string[]|null
   */
  protected $languages;

  /**
   * The search keys.
   *
   * If NULL, this will be a filter-only search.
   *
   * @var mixed
   */
  protected $keys;

  /**
   * The unprocessed search keys, as passed to the keys() method.
   *
   * @var mixed
   */
  protected $origKeys;

  /**
   * The fulltext fields that will be searched for the keys.
   *
   * @var array
   */
  protected $fields;

  /**
   * The root condition group associated with this query.
   *
   * @var \Drupal\search_api\Query\ConditionGroupInterface
   */
  protected $conditionGroup;

  /**
   * The sorts associated with this query.
   *
   * @var array
   */
  protected $sorts = [];

  /**
   * Information about whether the query has been aborted or not.
   *
   * @var \Drupal\Component\Render\MarkupInterface|string|true|null
   */
  protected $aborted;

  /**
   * Options configuring this query.
   *
   * @var array
   */
  protected $options;

  /**
   * The tags set on this query.
   *
   * @var string[]
   */
  protected $tags = [];

  /**
   * Flag for whether preExecute() was already called for this query.
   *
   * @var bool
   */
  protected $preExecuteRan = FALSE;

  /**
   * Flag for whether execute() was already called for this query.
   *
   * @var bool
   */
  protected $executed = FALSE;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|null
   */
  protected $moduleHandler;

  /**
   * The parse mode manager.
   *
   * @var \Drupal\search_api\ParseMode\ParseModePluginManager|null
   */
  protected $parseModeManager;

  /**
   * The display plugin manager.
   *
   * @var \Drupal\search_api\Display\DisplayPluginManagerInterface|null
   */
  protected $displayPluginManager;

  /**
   * The result cache service.
   *
   * @var \Drupal\search_api\Utility\QueryHelperInterface|null
   */
  protected $queryHelper;

  /**
   * The original query before preprocessing.
   *
   * @var static|null
   */
  protected $originalQuery;

  /**
   * Constructs a Query object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index the query should be executed on.
   * @param array $options
   *   (optional) Associative array of options configuring this query. See
   *   \Drupal\search_api\Query\QueryInterface::setOption() for a list of
   *   options that are recognized by default.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if a search on that index (or with those options) won't be
   *   possible.
   */
  public function __construct(IndexInterface $index, array $options = []) {
    if (!$index->status()) {
      $index_label = $index->label();
      throw new SearchApiException("Can't search on index '$index_label' which is disabled.");
    }
    $this->index = $index;
    $this->results = new ResultSet($this);
    $this->options = $options;
    $this->conditionGroup = $this->createConditionGroup('AND');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(IndexInterface $index, array $options = []) {
    return new static($index, $options);
  }

  /**
   * Retrieves the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function getModuleHandler() {
    return $this->moduleHandler ?: \Drupal::moduleHandler();
  }

  /**
   * Sets the module handler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The new module handler.
   *
   * @return $this
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    return $this;
  }

  /**
   * Retrieves the parse mode manager.
   *
   * @return \Drupal\search_api\ParseMode\ParseModePluginManager
   *   The parse mode manager.
   */
  public function getParseModeManager() {
    return $this->parseModeManager ?: \Drupal::service('plugin.manager.search_api.parse_mode');
  }

  /**
   * Sets the parse mode manager.
   *
   * @param \Drupal\search_api\ParseMode\ParseModePluginManager $parse_mode_manager
   *   The new parse mode manager.
   *
   * @return $this
   */
  public function setParseModeManager(ParseModePluginManager $parse_mode_manager) {
    $this->parseModeManager = $parse_mode_manager;
    return $this;
  }

  /**
   * Retrieves the display plugin manager.
   *
   * @return \Drupal\search_api\Display\DisplayPluginManagerInterface
   *   The display plugin manager.
   */
  public function getDisplayPluginManager() {
    return $this->displayPluginManager ?: \Drupal::service('plugin.manager.search_api.display');
  }

  /**
   * Sets the display plugin manager.
   *
   * @param \Drupal\search_api\Display\DisplayPluginManagerInterface $display_plugin_manager
   *   The new display plugin manager.
   *
   * @return $this
   */
  public function setDisplayPluginManager(DisplayPluginManagerInterface $display_plugin_manager) {
    $this->displayPluginManager = $display_plugin_manager;
    return $this;
  }

  /**
   * Retrieves the query helper.
   *
   * @return \Drupal\search_api\Utility\QueryHelperInterface
   *   The query helper.
   */
  public function getQueryHelper() {
    return $this->queryHelper ?: \Drupal::service('search_api.query_helper');
  }

  /**
   * Sets the query helper.
   *
   * @param \Drupal\search_api\Utility\QueryHelperInterface $query_helper
   *   The new query helper.
   *
   * @return $this
   */
  public function setQueryHelper(QueryHelperInterface $query_helper) {
    $this->queryHelper = $query_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchId($generate = TRUE) {
    if ($generate && !isset($this->searchId)) {
      static $num = 0;
      $this->searchId = 'search_' . ++$num;
    }
    return $this->searchId;
  }

  /**
   * {@inheritdoc}
   */
  public function setSearchId($search_id) {
    $this->searchId = $search_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayPlugin() {
    $display_manager = $this->getDisplayPluginManager();
    if (isset($this->searchId) && $display_manager->hasDefinition($this->searchId)) {
      return $display_manager->createInstance($this->searchId);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParseMode() {
    if (!$this->parseMode) {
      $this->parseMode = $this->getParseModeManager()->createInstance('terms');
    }
    return $this->parseMode;
  }

  /**
   * {@inheritdoc}
   */
  public function setParseMode(ParseModeInterface $parse_mode) {
    $this->parseMode = $parse_mode;
    if (is_scalar($this->origKeys)) {
      $this->keys = $parse_mode->parseInput($this->origKeys);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguages() {
    return $this->languages;
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguages(array $languages = NULL) {
    $this->languages = isset($languages) ? array_values($languages) : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function createConditionGroup($conjunction = 'AND', array $tags = []) {
    return new ConditionGroup($conjunction, $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function keys($keys = NULL) {
    $this->origKeys = $keys;
    if (is_scalar($keys)) {
      $this->keys = $this->getParseMode()->parseInput("$keys");
    }
    else {
      $this->keys = $keys;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFulltextFields(array $fields = NULL) {
    $this->fields = $fields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addConditionGroup(ConditionGroupInterface $condition_group) {
    $this->conditionGroup->addConditionGroup($condition_group);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCondition($field, $value, $operator = '=') {
    $this->conditionGroup->addCondition($field, $value, $operator);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sort($field, $order = self::SORT_ASC) {
    $order = strtoupper(trim($order));
    $order = $order == self::SORT_DESC ? self::SORT_DESC : self::SORT_ASC;
    if (!isset($this->sorts[$field])) {
      $this->sorts[$field] = $order;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function range($offset = NULL, $limit = NULL) {
    $this->options['offset'] = $offset;
    $this->options['limit'] = $limit;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessingLevel() {
    return $this->processingLevel;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessingLevel($level) {
    $this->processingLevel = $level;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function abort($error_message = NULL) {
    $this->aborted = isset($error_message) ? $error_message : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function wasAborted() {
    return $this->aborted !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAbortMessage() {
    return !is_bool($this->aborted) ? $this->aborted : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if ($this->hasExecuted()) {
      return $this->results;
    }

    $this->executed = TRUE;

    // Check for aborted status both before and after calling preExecute().
    if ($this->shouldAbort()) {
      return $this->results;
    }

    // Prepare the query for execution by the server.
    $this->preExecute();

    if ($this->shouldAbort()) {
      return $this->results;
    }

    // Execute query.
    $this->index->getServerInstance()->search($this);

    // Postprocess the search results.
    $this->postExecute();

    return $this->results;
  }

  /**
   * Determines whether the query should be aborted.
   *
   * Also prepares the result set if the query should be aborted.
   *
   * @return bool
   *   TRUE if the query should be aborted, FALSE otherwise.
   */
  protected function shouldAbort() {
    if (!$this->wasAborted() && $this->languages !== []) {
      return FALSE;
    }
    if (!$this->originalQuery) {
      $this->originalQuery = clone $this;
    }
    $this->postExecute();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preExecute() {
    // Make sure to only execute this once per query, and not for queries with
    // the "none" processing level.
    if (!$this->preExecuteRan) {
      $this->originalQuery = clone $this;
      $this->originalQuery->executed = FALSE;
      $this->preExecuteRan = TRUE;

      if ($this->processingLevel == self::PROCESSING_NONE) {
        return;
      }

      // Preprocess query.
      $this->index->preprocessSearchQuery($this);

      // Let modules alter the query.
      $hooks = ['search_api_query'];
      foreach ($this->tags as $tag) {
        $hooks[] = "search_api_query_$tag";
      }
      $this->getModuleHandler()->alter($hooks, $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postExecute() {
    if ($this->processingLevel == self::PROCESSING_NONE) {
      return;
    }

    // Postprocess results.
    $this->index->postprocessSearchResults($this->results);

    // Let modules alter the results.
    $hooks = ['search_api_results'];
    foreach ($this->tags as $tag) {
      $hooks[] = "search_api_results_$tag";
    }
    $this->getModuleHandler()->alter($hooks, $this->results);

    // Store the results in the static cache.
    $this->getQueryHelper()->addResults($this->results);
  }

  /**
   * {@inheritdoc}
   */
  public function hasExecuted() {
    return $this->executed;
  }

  /**
   * {@inheritdoc}
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function &getKeys() {
    return $this->keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalKeys() {
    return $this->origKeys;
  }

  /**
   * {@inheritdoc}
   */
  public function &getFulltextFields() {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionGroup() {
    return $this->conditionGroup;
  }

  /**
   * {@inheritdoc}
   */
  public function &getSorts() {
    return $this->sorts;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($name, $default = NULL) {
    return array_key_exists($name, $this->options) ? $this->options[$name] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($name, $value) {
    $old = $this->getOption($name);
    $this->options[$name] = $value;
    return $old;
  }

  /**
   * {@inheritdoc}
   */
  public function &getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function addTag($tag) {
    $this->tags[$tag] = $tag;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTag($tag) {
    return isset($this->tags[$tag]);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAllTags() {
    return !array_diff_key(array_flip(func_get_args()), $this->tags);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAnyTag() {
    return (bool) array_intersect_key(array_flip(func_get_args()), $this->tags);
  }

  /**
   * {@inheritdoc}
   */
  public function &getTags() {
    return $this->tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalQuery() {
    return $this->originalQuery ?: clone $this;
  }

  /**
   * {@inheritdoc}
   */
  public function __clone() {
    $this->results = $this->getResults()->getCloneForQuery($this);
    $this->conditionGroup = clone $this->conditionGroup;
    if ($this->originalQuery) {
      $this->originalQuery = clone $this->originalQuery;
    }
    if ($this->parseMode) {
      $this->parseMode = clone $this->parseMode;
    }
  }

  /**
   * Implements the magic __sleep() method to avoid serializing the index.
   */
  public function __sleep() {
    $this->indexId = $this->index->id();
    $keys = $this->traitSleep();
    return array_diff($keys, ['index']);
  }

  /**
   * Implements the magic __wakeup() method to reload the query's index.
   */
  public function __wakeup() {
    if (!isset($this->index)
        && !empty($this->indexId)
        && \Drupal::hasContainer()
        && \Drupal::getContainer()->has('entity_type.manager')) {
      $this->index = \Drupal::entityTypeManager()
        ->getStorage('search_api_index')
        ->load($this->indexId);
      $this->indexId = NULL;
    }

    $this->traitWakeup();
  }

  /**
   * Implements the magic __toString() method to simplify debugging.
   */
  public function __toString() {
    $ret = 'Index: ' . $this->index->id() . "\n";
    $ret .= 'Keys: ' . str_replace("\n", "\n  ", var_export($this->origKeys, TRUE)) . "\n";
    if (isset($this->keys)) {
      $ret .= 'Parsed keys: ' . str_replace("\n", "\n  ", var_export($this->keys, TRUE)) . "\n";
      $ret .= 'Searched fields: ' . (isset($this->fields) ? implode(', ', $this->fields) : '[ALL]') . "\n";
    }
    if (isset($this->languages)) {
      $ret .= 'Searched languages: ' . implode(', ', $this->languages) . "\n";
    }
    if ($conditions = (string) $this->conditionGroup) {
      $conditions = str_replace("\n", "\n  ", $conditions);
      $ret .= "Conditions:\n  $conditions\n";
    }
    if ($this->sorts) {
      $sorts = [];
      foreach ($this->sorts as $field => $order) {
        $sorts[] = "$field $order";
      }
      $ret .= 'Sorting: ' . implode(', ', $sorts) . "\n";
    }
    $options = $this->sanitizeOptions($this->options);
    $options = str_replace("\n", "\n  ", var_export($options, TRUE));
    $ret .= 'Options: ' . $options . "\n";
    return $ret;
  }

  /**
   * Sanitizes an array of options in a way that plays nice with var_export().
   *
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   The sanitized options.
   */
  protected function sanitizeOptions(array $options) {
    foreach ($options as $key => $value) {
      if (is_object($value)) {
        $options[$key] = 'object (' . get_class($value) . ')';
      }
      elseif (is_array($value)) {
        $options[$key] = $this->sanitizeOptions($value);
      }
    }
    return $options;
  }

}
