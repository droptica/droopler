<?php

namespace Drupal\search_api\Task;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a service for managing pending index tasks.
 */
class IndexTaskManager implements IndexTaskManagerInterface, EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The Search API task type used by this service for "track items" tasks.
   */
  const TRACK_ITEMS_TASK_TYPE = 'trackItems';

  /**
   * The Search API task manager.
   *
   * @var \Drupal\search_api\Task\TaskManagerInterface
   */
  protected $taskManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an IndexTaskManager object.
   *
   * @param \Drupal\search_api\Task\TaskManagerInterface $task_manager
   *   The Search API task manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(TaskManagerInterface $task_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->taskManager = $task_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['search_api.task.' . self::TRACK_ITEMS_TASK_TYPE][] = ['trackItems'];

    return $events;
  }

  /**
   * Processes all pending index tasks inside a batch run.
   *
   * @param array|\ArrayAccess $context
   *   The current batch context.
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The config importer.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if any error occurred while tracking items.
   */
  public static function processIndexTasks(&$context, ConfigImporter $config_importer) {
    $index_task_manager = \Drupal::getContainer()
      ->get('search_api.index_task_manager');

    if (!isset($context['sandbox']['indexes'])) {
      $context['sandbox']['indexes'] = [];

      $indexes = \Drupal::entityTypeManager()
        ->getStorage('search_api_index')
        ->loadByProperties([
          'status' => TRUE,
        ]);
      $deleted = $config_importer->getUnprocessedConfiguration('delete');

      /** @var \Drupal\search_api\IndexInterface $index */
      foreach ($indexes as $index_id => $index) {
        if (!$index_task_manager->isTrackingComplete($index) && !in_array($index->getConfigDependencyName(), $deleted)) {
          $context['sandbox']['indexes'][] = $index_id;
        }
      }
      $context['sandbox']['total'] = count($context['sandbox']['indexes']);
      if (!$context['sandbox']['total']) {
        $context['finished'] = 1;
        return;
      }
    }

    $index_id = array_shift($context['sandbox']['indexes']);
    $index = Index::load($index_id);
    try {
      if (!($index_task_manager->addItemsOnce($index))) {
        array_unshift($context['sandbox']['indexes'], $index_id);
      }
    }
    catch (SearchApiException $e) {
      watchdog_exception('search_api', $e);
    }

    if (empty($context['sandbox']['indexes'])) {
      $context['finished'] = 1;
    }
    else {
      $finished = $context['sandbox']['total'] - count($context['sandbox']['indexes']);
      $context['finished'] = $finished / $context['sandbox']['total'];
      $args = [
        '%index' => $index->label(),
        '@num' => $finished + 1,
        '@total' => $context['sandbox']['total'],
      ];
      $context['message'] = \Drupal::translation()
        ->translate('Tracking items for search index %index (@num of @total)', $args);
    }
  }

  /**
   * Tracks items according to the given event.
   *
   * @param \Drupal\search_api\Task\TaskEvent $event
   *   The task event.
   */
  public function trackItems(TaskEvent $event) {
    $event->stopPropagation();

    $task = $event->getTask();
    $index = $task->getIndex();

    if (!$index->hasValidTracker()) {
      $args['%index'] = $index->label();
      $message = new FormattableMarkup('Index %index does not have a valid tracker set.', $args);
      $event->setException(new SearchApiException($message));
      return;
    }

    $data = $task->getData();
    $datasource_id = $data['datasource'];

    $reschedule = FALSE;
    if ($index->isValidDatasource($datasource_id)) {
      $raw_ids = $index->getDatasource($datasource_id)->getItemIds($data['page']);
      if ($raw_ids !== NULL) {
        $reschedule = TRUE;
        if ($raw_ids) {
          $index->startBatchTracking();
          $index->trackItemsInserted($datasource_id, $raw_ids);
          $index->stopBatchTracking();
        }
      }
    }

    if ($reschedule) {
      ++$data['page'];
      $this->taskManager->addTask(self::TRACK_ITEMS_TASK_TYPE, NULL, $index, $data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function startTracking(IndexInterface $index, array $datasource_ids = NULL) {
    if (!isset($datasource_ids)) {
      $datasource_ids = $index->getDatasourceIds();
    }

    foreach ($datasource_ids as $datasource_id) {
      $data = [
        'datasource' => $datasource_id,
        'page' => 0,
      ];
      $this->taskManager->addTask(self::TRACK_ITEMS_TASK_TYPE, NULL, $index, $data);
    }
  }

  /**
   * Gets a set of conditions for finding the tracking tasks of the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to retrieve tasks.
   *
   * @return array
   *   An array of conditions to pass to the Search API task manager.
   */
  protected function getTaskConditions(IndexInterface $index) {
    return [
      'type' => self::TRACK_ITEMS_TASK_TYPE,
      'index_id' => $index->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function addItemsOnce(IndexInterface $index) {
    return !$this->taskManager->executeSingleTask($this->getTaskConditions($index));
  }

  /**
   * {@inheritdoc}
   */
  public function addItemsBatch(IndexInterface $index) {
    $this->taskManager->setTasksBatch($this->getTaskConditions($index));
  }

  /**
   * {@inheritdoc}
   */
  public function addItemsAll(IndexInterface $index) {
    $this->taskManager->executeAllTasks($this->getTaskConditions($index));
  }

  /**
   * {@inheritdoc}
   */
  public function stopTracking(IndexInterface $index, array $datasource_ids = NULL) {
    $valid_tracker = $index->hasValidTracker();
    if (!isset($datasource_ids)) {
      $this->taskManager->deleteTasks($this->getTaskConditions($index));
      if ($valid_tracker) {
        $index->getTrackerInstance()->trackAllItemsDeleted();
      }
      return;
    }

    // Catch the case of being called with an empty array of datasources.
    if (!$datasource_ids) {
      return;
    }

    $tasks = $this->taskManager->loadTasks($this->getTaskConditions($index));
    foreach ($tasks as $task_id => $task) {
      $data = $task->getData();
      if (in_array($data['datasource'], $datasource_ids)) {
        $this->taskManager->deleteTask($task_id);
      }
    }

    foreach ($datasource_ids as $datasource_id) {
      $index->getTrackerInstance()->trackAllItemsDeleted($datasource_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isTrackingComplete(IndexInterface $index) {
    return !$this->taskManager->getTasksCount($this->getTaskConditions($index));
  }

}
