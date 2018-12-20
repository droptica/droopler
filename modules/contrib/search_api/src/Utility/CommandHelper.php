<?php

namespace Drupal\search_api\Utility;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search_api\ConsoleException;
use Drupal\search_api\IndexBatchHelper;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Provides functionality to be used by CLI tools.
 */
class CommandHelper implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The storage for search index entities.
   *
   * @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage
   */
  protected $indexStorage;

  /**
   * The storage for search server entities.
   *
   * @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage
   */
  protected $serverStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A callable for translating strings.
   *
   * @var callable
   */
  protected $translationFunction;

  /**
   * Constructs a CommandHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string|callable $translation_function
   *   (optional) A callable for translating strings.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the "search_api_index" or "search_api_server" entity types are
   *   unknown.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, $translation_function = 'dt') {
    $this->entityTypeManager = $entity_type_manager;
    $this->indexStorage = $entity_type_manager->getStorage('search_api_index');
    $this->serverStorage = $entity_type_manager->getStorage('search_api_server');
    $this->moduleHandler = $module_handler;
    $this->translationFunction = $translation_function;
  }

  /**
   * Lists all search indexes.
   *
   * @return array
   *   An associative array, keyed by search index ID, each value an associative
   *   array with the following keys:
   *   - id: The ID of the search index.
   *   - name: The human readable name of the search index.
   *   - server: The ID of the server associated with the search index.
   *   - serverName: The human readable name of the server associated with the
   *     search index.
   *   - types: An array of entity type IDs that are tracked in the index.
   *   - typeNames: An array of human readable entity type labels that are
   *     tracked in the index.
   *   - status: Either "enabled" or "disabled".
   *   - limit: The number of items that are processed in a single cron run.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an index has a server which couldn't be loaded.
   */
  public function indexListCommand() {
    $indexes = $this->loadIndexes();
    if (!$indexes) {
      return [];
    }

    $rows = [];
    $none = '(' . $this->t('none') . ')';
    $enabled = $this->t('enabled');
    $disabled = $this->t('disabled');

    foreach ($indexes as $index) {
      $types = [];
      $type_names = [];
      foreach ($index->getDatasources() as $datasource) {
        $types[] = $datasource->getEntityTypeId();
        $type_names[] = (string) $datasource->label();
      }
      $rows[$index->id()] = [
        'id' => $index->id(),
        'name' => $index->label(),
        'server' => $index->getServerId() ?: $none,
        'serverName' => $index->getServerId() ? $index->getServerInstance()->label() : $none,
        'types' => $types,
        'typeNames' => $type_names,
        'status' => $index->status() ? $enabled : $disabled,
        'limit' => (int) $index->getOption('cron_limit'),
      ];
    }

    return $rows;
  }

  /**
   * Lists all search indexes with their status.
   *
   * @param string[]|null $indexId
   *   (optional) An array of search index IDs, or NULL to list the status of
   *   all indexes.
   *
   * @return array
   *   An associative array, keyed by search index ID, each value an associative
   *   array with the following keys:
   *   - id: The ID of the search index.
   *   - name: The human readable name of the search index.
   *   - complete: a percentage of indexation.
   *   - indexed: The amount of indexed items.
   *   - total: The total amount of items.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if one of the affected indexes had an invalid tracker set.
   */
  public function indexStatusCommand(array $indexId = NULL) {
    $indexes = $this->loadIndexes($indexId);
    if (!$indexes) {
      return [];
    }

    $rows = [];
    foreach ($indexes as $index) {
      $indexed = $index->getTrackerInstance()->getIndexedItemsCount();
      $total = $index->getTrackerInstance()->getTotalItemsCount();

      $complete = '-';
      if ($total > 0) {
        $complete = (100 * round($indexed / $total, 3)) . '%';
      }

      $rows[$index->id()] = [
        'id' => $index->id(),
        'name' => $index->label(),
        'complete' => $complete,
        'indexed' => $indexed,
        'total' => $total,
      ];
    }

    return $rows;
  }

  /**
   * Enables one or more disabled search indexes.
   *
   * @param array $index_ids
   *   (optional) An array of machine names of indexes to enable. If omitted all
   *   indexes will be enabled.
   *
   * @throws \Drupal\search_api\ConsoleException
   *   Thrown if no indexes could be loaded.
   */
  public function enableIndexCommand(array $index_ids = NULL) {
    if (!$this->getIndexCount()) {
      throw new ConsoleException($this->t('There are no indexes defined. Please create an index before trying to enable it.'));
    }

    $indexes = $this->loadIndexes($index_ids);

    if (!$indexes) {
      throw new ConsoleException($this->t('You must specify at least one index to enable.'));
    }

    foreach ($indexes as $index) {
      if (!$index->status()) {
        $this->setIndexState($index);
      }
    }
  }

  /**
   * Disables one or more enabled search indexes.
   *
   * @param array $index_ids
   *   (optional) An array of machine names of indexes to disable. If omitted
   *   all indexes will be disabled.
   *
   * @throws \Drupal\search_api\ConsoleException
   *   Thrown if no indexes could be loaded.
   */
  public function disableIndexCommand(array $index_ids = NULL) {
    if (!$this->getIndexCount()) {
      throw new ConsoleException($this->t('There are no indexes defined. Please create an index before trying to disable it.'));
    }

    $indexes = $this->loadIndexes($index_ids);

    if (!$indexes) {
      throw new ConsoleException($this->t('You must specify at least one index to disable.'));
    }

    foreach ($indexes as $index) {
      if ($index->status()) {
        $this->setIndexState($index, FALSE);
      }
    }
  }

  /**
   * Indexes items on one or more indexes.
   *
   * @param string[]|null $indexIds
   *   (optional) An array of index IDs, or NULL if we should index items for
   *   all enabled indexes.
   * @param int|null $limit
   *   (optional) The maximum number of items to index, or NULL to index all
   *   items.
   * @param int|null $batchSize
   *   (optional) The maximum number of items to process per batch, or NULL to
   *   index all items at once.
   *
   * @return bool
   *   TRUE if any indexes could be loaded, FALSE otherwise.
   *
   * @throws \Drupal\search_api\ConsoleException
   *   Thrown if an indexing batch process could not be created.
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if one of the affected indexes had an invalid tracker set.
   */
  public function indexItemsToIndexCommand(array $indexIds = NULL, $limit = NULL, $batchSize = NULL) {
    $indexes = $this->loadIndexes($indexIds);
    if (!$indexes) {
      return FALSE;
    }

    $batch_set = FALSE;
    foreach ($indexes as $index) {
      if (!$index->status() || $index->isReadOnly()) {
        continue;
      }
      $tracker = $index->getTrackerInstance();
      $remaining = $tracker->getTotalItemsCount() - $tracker->getIndexedItemsCount();

      if (!$remaining) {
        $this->logger->info($this->t("The index @index is up to date.", ['@index' => $index->label()]));
        continue;
      }
      else {
        $arguments = [
          '@remaining' => $remaining,
          '@limit' => $limit ? $limit : $this->t('all'),
          '@index' => $index->label(),
        ];
        $this->logger->info($this->t("Found @remaining items to index for @index. Indexing @limit items.", $arguments));
      }

      // If we pass NULL, it would be used as "no items". -1 is the correct way
      // to index all items.
      $current_limit = $limit ?: -1;

      // Get the default batch size.
      if (!$batchSize) {
        $cron_limit = $index->getOption('cron_limit');
        $batchSize = $cron_limit ?: \Drupal::configFactory()
          ->get('search_api.settings')
          ->get('default_cron_limit');
      }

      // Get the number items to index.
      if (!isset($current_limit) || !is_int($current_limit += 0) || $current_limit <= 0) {
        $current_limit = $remaining;
      }

      $arguments = [
        '@index' => $index->label(),
        '@limit' => $current_limit,
        '@batch_size' => $batchSize,
      ];
      $this->logger->info($this->t("Indexing a maximum number of @limit items (@batch_size items per batch run) for the index '@index'.", $arguments));

      // Create the batch.
      try {
        IndexBatchHelper::create($index, $batchSize, $current_limit);
        $batch_set = TRUE;
      }
      catch (SearchApiException $e) {
        throw new ConsoleException($this->t("Couldn't create a batch, please check the batch size and limit parameters."));
      }
    }

    return $batch_set;
  }

  /**
   * Resets the tracker for an index, optionally filtering on entity types.
   *
   * @param string[]|null $indexIds
   *   (optional) An array of index IDs, or NULL if we should reset the trackers
   *   of all indexes.
   * @param string[] $entityTypes
   *   (optional) An array of entity types for which to reset the tracker.
   *
   * @return bool
   *   TRUE if any index was affected, FALSE otherwise.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if one of the affected indexes had an invalid tracker set, or some
   *   other internal error occurred.
   */
  public function resetTrackerCommand(array $indexIds = NULL, array $entityTypes = []) {
    $indexes = $this->loadIndexes($indexIds);
    if (!$indexes) {
      return FALSE;
    }

    foreach ($indexes as $index) {
      if (!$index->status()) {
        continue;
      }
      if (!empty($entityTypes)) {
        $datasources = $index->getDatasources();
        $reindexed_datasources = [];
        foreach ($datasources as $datasource_id => $datasource) {
          if (in_array($datasource->getEntityTypeId(), $entityTypes)) {
            $index->getTrackerInstance()->trackAllItemsUpdated($datasource_id);
            $reindexed_datasources[] = $datasource->label();
          }
        }
        $this->moduleHandler->invokeAll('search_api_index_reindex', [$index, FALSE]);
        $arguments = [
          '!index' => $index->label(),
          '!datasources' => implode(', ', $reindexed_datasources),
        ];
        $this->logger->info($this->t('The following datasources of !index were successfully scheduled for reindexing: !datasources.', $arguments));
      }
      else {
        $index->reindex();
        $this->logger->info($this->t('!index was successfully scheduled for reindexing.', ['!index' => $index->label()]));
      }
    }

    return TRUE;
  }

  /**
   * Deletes all items from one or more indexes.
   *
   * @param string[]|null $indexIds
   *   (optional) An array of index IDs, or NULL if we should delete all items
   *   from all indexes.
   *
   * @return bool
   *   TRUE when the clearing was successful, FALSE when no indexes were found.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if one of the affected indexes had an invalid tracker set, or some
   *   other internal error occurred.
   */
  public function clearIndexCommand(array $indexIds = NULL) {
    $indexes = $this->loadIndexes($indexIds);
    if (!$indexes) {
      return FALSE;
    }
    foreach ($indexes as $index) {
      if ($index->status()) {
        $index->clear();
        $this->logger->info($this->t('@index was successfully cleared.', ['@index' => $index->label()]));
      }
    }

    return TRUE;
  }

  /**
   * Returns an array of results.
   *
   * @param string $indexId
   *   The index to search in.
   * @param string|null $keyword
   *   (optional) The word to search for.
   *
   * @return array
   *   An array of results, each of which is represented by an associative
   *   array with the following keys:
   *   - id: The internal ID of the item.
   *   - label: The label of the item, or NULL if it could not be determined.
   *
   * @throws \Drupal\search_api\ConsoleException
   *   Thrown if searching failed for any reason.
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if no search query could be created for the given index, for
   *   example because it is disabled or its server could not be loaded.
   */
  public function searchIndexCommand($indexId, $keyword = NULL) {
    $indexes = $this->loadIndexes([$indexId]);
    if (empty($indexes[$indexId])) {
      throw new ConsoleException($this->t('@index was not found'));
    }

    $query = $indexes[$indexId]->query();
    if ($keyword !== NULL) {
      $query->keys($keyword);
    }

    $query->range(0, 10);
    try {
      $results = $query->execute();
    }
    catch (SearchApiException $e) {
      throw new ConsoleException($e->getMessage(), 0, $e);
    }

    $rows = [];
    foreach ($results->getResultItems() as $item) {
      try {
        $label = $item->getDatasource()
          ->getItemLabel($item->getOriginalObject());
      }
      catch (SearchApiException $e) {
        $label = NULL;
      }
      $rows[] = [
        'id' => $item->getId(),
        'label' => $label,
      ];
    }

    return $rows;
  }

  /**
   * Returns a list of servers created on the page.
   *
   * @return array
   *   An associative array, keyed by search server ID, each value an
   *   associative array with the following keys:
   *   - id: The ID of the search server.
   *   - name: The human readable name of the search server.
   *   - status: The enabled status of the server.
   *
   * @throws \Drupal\search_api\ConsoleException
   *   Thrown if no servers could be loaded.
   */
  public function serverListCommand() {
    $servers = $this->loadServers();
    if (count($servers) === 0) {
      throw new ConsoleException($this->t('There are no servers present.'));
    }

    $rows = [];
    foreach ($servers as $server) {
      $rows[$server->id()] = [
        'id' => $server->id(),
        'name' => $server->label(),
        'status' => $server->status() ? $this->t('enabled') : $this->t('disabled'),
      ];
    }

    return $rows;
  }

  /**
   * Enables a server.
   *
   * @param string $serverId
   *   The server's ID.
   *
   * @throws \Drupal\search_api\ConsoleException
   *   Thrown if the server couldn't be loaded.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if an internal error occurred when saving the server.
   */
  public function enableServerCommand($serverId) {
    $servers = $this->loadServers([$serverId]);
    if (empty($servers)) {
      throw new ConsoleException($this->t('The server could not be loaded.'));
    }
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->reloadEntityOverrideFree(reset($servers));
    $server->setStatus(TRUE)->save();
  }

  /**
   * Disables a server.
   *
   * @param string $serverId
   *   The server's ID.
   *
   * @throws \Drupal\search_api\ConsoleException
   *   Thrown if the server couldn't be loaded.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if an internal error occurred when saving the server.
   */
  public function disableServerCommand($serverId) {
    $servers = $this->loadServers([$serverId]);
    if (empty($servers)) {
      throw new ConsoleException($this->t('The server could not be loaded.'));
    }
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->reloadEntityOverrideFree(reset($servers));
    $server->setStatus(FALSE)->save();
  }

  /**
   * Clears all indexes on a server.
   *
   * @param string $serverId
   *   The ID of the server to clear.
   *
   * @throws \Drupal\search_api\ConsoleException
   *   Thrown if the server couldn't be loaded.
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if one of the affected indexes had an invalid tracker set, or some
   *   other internal error occurred.
   */
  public function clearServerCommand($serverId) {
    $servers = $this->loadServers([$serverId]);
    if (empty($servers)) {
      throw new ConsoleException($this->t('The server could not be loaded.'));
    }
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->reloadEntityOverrideFree(reset($servers));

    foreach ($server->getIndexes() as $index) {
      $index->clear();
    }
  }

  /**
   * Switches an index to another server.
   *
   * @param string $indexId
   *   The ID of the index.
   * @param string $serverId
   *   The ID of the index's new server.
   *
   * @throws \Drupal\search_api\ConsoleException
   *   If either the index or the server couldn't be loaded.
   */
  public function setIndexServerCommand($indexId, $serverId) {
    // Fetch current index and server data.
    $index = $this->loadIndexes([$indexId]);
    $server = $this->loadServers([$serverId]);

    $index = reset($index);
    $server = reset($server);

    if (!$index) {
      throw new ConsoleException($this->t('Invalid index ID "@index_id".', ['@index_id' => $indexId]));
    }
    if (!$server) {
      throw new ConsoleException($this->t('Invalid server ID "@server_id".', ['@server_id' => $serverId]));
    }

    // Set the new server on the index.
    try {
      /** @var \Drupal\search_api\IndexInterface $index */
      $index = $this->reloadEntityOverrideFree($index);
      $index->setServer($server);
      $index->save();
      $this->logger->info($this->t('Index @index has been set to use server @server and items have been queued for indexing.', ['@index' => $indexId, '@server' => $serverId]));
    }
    catch (EntityStorageException $e) {
      $this->logger->warning($e->getMessage());
      $this->logger->warning($this->t('There was an error setting index @index to use server @server, or this index is already configured to use this server.', ['@index' => $indexId, '@server' => $serverId]));
    }
  }

  /**
   * Returns the indexes with the given IDs.
   *
   * @param array|null $indexIds
   *   (optional) The IDs of the search indexes to return, or NULL to load all
   *   indexes. An array with a single NULL value is interpreted the same way as
   *   passing NULL.
   *
   * @return \Drupal\search_api\IndexInterface[]
   *   An array of search indexes.
   */
  public function loadIndexes(array $indexIds = NULL) {
    if ($indexIds === [NULL]) {
      $indexIds = NULL;
    }
    return $this->indexStorage->loadMultiple($indexIds);
  }

  /**
   * Returns the servers with the given IDs.
   *
   * @param array|null $serverIds
   *   (optional) The IDs of the search servers to return, or NULL to load all
   *   servers.
   *
   * @return \Drupal\search_api\ServerInterface[]
   *   An array of search servers.
   */
  public function loadServers(array $serverIds = NULL) {
    return $this->serverStorage->loadMultiple($serverIds);
  }

  /**
   * Returns the total number of search indexes.
   *
   * @return int
   *   The number of search indexes on this site.
   */
  public function getIndexCount() {
    return count($this->loadIndexes());
  }

  /**
   * Changes the state of a single index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to be enabled.
   * @param bool $enable
   *   (optional) TRUE to enable, FALSE to disable the index.
   */
  public function setIndexState(IndexInterface $index, $enable = TRUE) {
    $state_label = $enable ? $this->t('enabled') : $this->t('disabled');
    $method = $enable ? 'enable' : 'disable';

    if ($index->status() == $enable) {
      $this->logger->info($this->t("The index @index is already @desired_state.", ['@index' => $index->label(), '@desired_state' => $state_label]));
      return;
    }
    if (!$index->getServerId()) {
      $this->logger->warning($this->t("Index @index could not be @desired_state because it is not bound to any server.", ['@index' => $index->label(), '@desired_state' => $state_label]));
      return;
    }

    $index = $this->reloadEntityOverrideFree($index);
    $index->$method()->save();
    $this->logger->info($this->t("The index @index was successfully @desired_state.", ['@index' => $index->label(), '@desired_state' => $state_label]));
  }

  /**
   * Loads an override-free copy of a config entity, for saving.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity to reload.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The override-free version of the entity, or NULL if it couldn't be
   *   loaded.
   */
  public function reloadEntityOverrideFree(ConfigEntityInterface $entity) {
    try {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      return $storage->loadOverrideFree($entity->id());
    }
    catch (InvalidPluginDefinitionException $e) {
      return NULL;
    }
  }

  /**
   * Translates a string using the set translation method.
   *
   * @param string $message
   *   The message to translate.
   * @param array $arguments
   *   (optional) The translation arguments.
   *
   * @return string
   *   The translated message.
   */
  public function t($message, array $arguments = []) {
    return call_user_func_array($this->translationFunction, [
      $message,
      $arguments,
    ]);
  }

}
