<?php

namespace Drupal\search_api\Utility;

use Drupal\Core\DestructableInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\SearchApiException;

/**
 * Provides a service for indexing items at the end of the page request.
 */
class PostRequestIndexing implements PostRequestIndexingInterface, DestructableInterface {

  use LoggerTrait;

  /**
   * Indexing operations that should be executed at the end of the page request.
   *
   * The array is keyed by index ID and has arrays of item IDs to index for that
   * search index as values.
   *
   * @var string[][]
   */
  protected $operations = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    foreach ($this->operations as $index_id => $item_ids) {
      try {
        $storage = $this->entityTypeManager->getStorage('search_api_index');
      }
      // @todo @todo Replace with multi-catch for
      //   InvalidPluginDefinitionException and PluginNotFoundException once we
      //   depend on PHP 7.1+.
      catch (\Exception $e) {
        // It might be possible that the module got uninstalled during the rest
        // of the page request, or something else happened. To be on the safe
        // side, catch the exception in case the entity type isn't found.
        return;
      }

      /** @var \Drupal\search_api\IndexInterface $index */
      $index = $storage->load($index_id);
      // It's possible that the index was deleted in the meantime, so make sure
      // it's actually there.
      if (!$index) {
        continue;
      }

      try {
        $items = $index->loadItemsMultiple($item_ids);
        if ($items) {
          $index->indexSpecificItems($items);
        }
      }
      catch (SearchApiException $e) {
        $vars['%index'] = $index->label();
        watchdog_exception('search_api', $e, '%type while trying to index items on %index: @message in %function (line %line of %file).', $vars);
      }

      // We usually shouldn't be called twice in a page request, but no harm in
      // being too careful: Remove the operation once it was executed correctly.
      unset($this->operations[$index_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function registerIndexingOperation($index_id, array $item_ids) {
    foreach ($item_ids as $item_id) {
      $this->operations[$index_id][$item_id] = $item_id;
    }
  }

}
