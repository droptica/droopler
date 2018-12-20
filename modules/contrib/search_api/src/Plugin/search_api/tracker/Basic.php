<?php

namespace Drupal\search_api\Plugin\search_api\tracker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Tracker\TrackerPluginBase;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a tracker implementation which uses a FIFO-like processing order.
 *
 *  @SearchApiTracker(
 *   id = "default",
 *   label = @Translation("Default"),
 *   description = @Translation("Default index tracker which uses a simple database table for tracking items.")
 * )
 */
class Basic extends TrackerPluginBase implements PluginFormInterface {

  use LoggerTrait;
  use PluginFormTrait;

  /**
   * Status value that represents items which are indexed in their latest form.
   */
  const STATUS_INDEXED = 0;

  /**
   * Status value that represents items which still need to be indexed.
   */
  const STATUS_NOT_INDEXED = 1;

  /**
   * The database connection used by this plugin.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|null
   */
  protected $timeService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $tracker */
    $tracker = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $tracker->setDatabaseConnection($container->get('database'));
    $tracker->setTimeService($container->get('datetime.time'));

    return $tracker;
  }

  /**
   * Retrieves the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection used by this plugin.
   */
  public function getDatabaseConnection() {
    return $this->connection ?: \Drupal::database();
  }

  /**
   * Sets the database connection.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   *
   * @return $this
   */
  public function setDatabaseConnection(Connection $connection) {
    $this->connection = $connection;
    return $this;
  }

  /**
   * Retrieves the time service.
   *
   * @return \Drupal\Component\Datetime\TimeInterface
   *   The time service.
   */
  public function getTimeService() {
    return $this->timeService ?: \Drupal::time();
  }

  /**
   * Sets the time service.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time_service
   *   The new time service.
   *
   * @return $this
   */
  public function setTimeService(TimeInterface $time_service) {
    $this->timeService = $time_service;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['indexing_order' => 'fifo'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['indexing_order'] = [
      '#type' => 'radios',
      '#title' => $this->t('Indexing order'),
      '#description' => $this->t('The order in which items will be indexed.'),
      '#options' => [
        'fifo' => $this->t('Index items in the same order in which they were saved'),
        'lifo' => $this->t('Index the most recent items first'),
      ],
      '#default_value' => $this->configuration['indexing_order'],
    ];

    return $form;
  }

  /**
   * Creates a SELECT statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A SELECT statement.
   */
  protected function createSelectStatement() {
    $select = $this->getDatabaseConnection()->select('search_api_item', 'sai');
    $select->condition('index_id', $this->getIndex()->id());
    return $select;
  }

  /**
   * Creates an INSERT statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\Insert
   *   An INSERT statement.
   */
  protected function createInsertStatement() {
    return $this->getDatabaseConnection()->insert('search_api_item')
      ->fields(['index_id', 'datasource', 'item_id', 'changed', 'status']);
  }

  /**
   * Creates an UPDATE statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\Update
   *   An UPDATE statement.
   */
  protected function createUpdateStatement() {
    return $this->getDatabaseConnection()->update('search_api_item')
      ->condition('index_id', $this->getIndex()->id());
  }

  /**
   * Creates a DELETE statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\Delete
   *   A DELETE Statement.
   */
  protected function createDeleteStatement() {
    return $this->getDatabaseConnection()->delete('search_api_item')
      ->condition('index_id', $this->getIndex()->id());
  }

  /**
   * Creates a SELECT statement which filters on the not indexed items.
   *
   * @param string|null $datasource_id
   *   (optional) If specified, only items of the datasource with that ID are
   *   retrieved.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A SELECT statement.
   */
  protected function createRemainingItemsStatement($datasource_id = NULL) {
    $select = $this->createSelectStatement();
    $select->fields('sai', ['item_id']);
    if ($datasource_id) {
      $select->condition('datasource', $datasource_id);
    }
    $select->condition('sai.status', $this::STATUS_NOT_INDEXED, '=');
    // Use the same direction for both sorts to avoid performance problems.
    $order = $this->configuration['indexing_order'] === 'lifo' ? 'DESC' : 'ASC';
    $select->orderBy('sai.changed', $order);
    // Add a secondary sort on item ID to make the order completely predictable.
    $select->orderBy('sai.item_id', $order);

    return $select;
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsInserted(array $ids) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $index_id = $this->getIndex()->id();
      // Process the IDs in chunks so we don't create an overly large INSERT
      // statement.
      foreach (array_chunk($ids, 1000) as $ids_chunk) {
        // We have to make sure we don't try to insert duplicate items.
        $select = $this->createSelectStatement()
          ->fields('sai', ['item_id']);
        $select->condition('item_id', $ids_chunk, 'IN');
        $existing = $select
          ->execute()
          ->fetchCol();
        $existing = array_flip($existing);

        $insert = $this->createInsertStatement();
        foreach ($ids_chunk as $item_id) {
          if (isset($existing[$item_id])) {
            continue;
          }
          list($datasource_id) = Utility::splitCombinedId($item_id);
          $insert->values([
            'index_id' => $index_id,
            'datasource' => $datasource_id,
            'item_id' => $item_id,
            'changed' => $this->getTimeService()->getRequestTime(),
            'status' => $this::STATUS_NOT_INDEXED,
          ]);
        }
        if ($insert->count()) {
          $insert->execute();
        }
      }
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsUpdated(array $ids = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      // Process the IDs in chunks so we don't create an overly large UPDATE
      // statement.
      $ids_chunks = ($ids !== NULL ? array_chunk($ids, 1000) : [NULL]);
      foreach ($ids_chunks as $ids_chunk) {
        $update = $this->createUpdateStatement();
        $update->fields([
          'changed' => $this->getTimeService()->getRequestTime(),
          'status' => $this::STATUS_NOT_INDEXED,
        ]);
        if ($ids_chunk) {
          $update->condition('item_id', $ids_chunk, 'IN');
        }
        // Update the status of unindexed items only if the item order is LIFO.
        // (Otherwise, an item that's regularly being updated might never get
        // indexed.)
        if ($this->configuration['indexing_order'] === 'fifo') {
          $update->condition('status', self::STATUS_INDEXED);
        }
        $update->execute();
      }
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackAllItemsUpdated($datasource_id = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $update = $this->createUpdateStatement();
      $update->fields([
        'changed' => $this->getTimeService()->getRequestTime(),
        'status' => $this::STATUS_NOT_INDEXED,
      ]);
      if ($datasource_id) {
        $update->condition('datasource', $datasource_id);
      }
      $update->execute();
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsIndexed(array $ids) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      // Process the IDs in chunks so we don't create an overly large UPDATE
      // statement.
      $ids_chunks = array_chunk($ids, 1000);
      foreach ($ids_chunks as $ids_chunk) {
        $update = $this->createUpdateStatement();
        $update->fields(['status' => $this::STATUS_INDEXED]);
        $update->condition('item_id', $ids_chunk, 'IN');
        $update->execute();
      }
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsDeleted(array $ids = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      // Process the IDs in chunks so we don't create an overly large DELETE
      // statement.
      $ids_chunks = ($ids !== NULL ? array_chunk($ids, 1000) : [NULL]);
      foreach ($ids_chunks as $ids_chunk) {
        $delete = $this->createDeleteStatement();
        if ($ids_chunk) {
          $delete->condition('item_id', $ids_chunk, 'IN');
        }
        $delete->execute();
      }
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackAllItemsDeleted($datasource_id = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $delete = $this->createDeleteStatement();
      if ($datasource_id) {
        $delete->condition('datasource', $datasource_id);
      }
      $delete->execute();
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingItems($limit = -1, $datasource_id = NULL) {
    try {
      $select = $this->createRemainingItemsStatement($datasource_id);
      if ($limit >= 0) {
        $select->range(0, $limit);
      }
      return $select->execute()->fetchCol();
    }
    catch (\Exception $e) {
      $this->logException($e);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalItemsCount($datasource_id = NULL) {
    try {
      $select = $this->createSelectStatement();
      if ($datasource_id) {
        $select->condition('datasource', $datasource_id);
      }
      return (int) $select->countQuery()->execute()->fetchField();
    }
    catch (\Exception $e) {
      $this->logException($e);
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexedItemsCount($datasource_id = NULL) {
    try {
      $select = $this->createSelectStatement();
      $select->condition('sai.status', $this::STATUS_INDEXED);
      if ($datasource_id) {
        $select->condition('datasource', $datasource_id);
      }
      return (int) $select->countQuery()->execute()->fetchField();
    }
    catch (\Exception $e) {
      $this->logException($e);
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingItemsCount($datasource_id = NULL) {
    try {
      $select = $this->createRemainingItemsStatement();
      if ($datasource_id) {
        $select->condition('datasource', $datasource_id);
      }
      return (int) $select->countQuery()->execute()->fetchField();
    }
    catch (\Exception $e) {
      $this->logException($e);
      return 0;
    }
  }

}
