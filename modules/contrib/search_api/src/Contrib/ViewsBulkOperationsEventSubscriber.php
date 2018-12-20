<?php

namespace Drupal\search_api\Contrib;

use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views_bulk_operations\ViewsBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides an event subscriber that interfaces with Views Bulk Operations.
 *
 * This will provide VBO integration for search views by enabling VBO to
 * retrieve the entities contained in search view result rows.
 *
 * @see \Drupal\views_bulk_operations\EventSubscriber\ViewsBulkOperationsEventSubscriber
 */
class ViewsBulkOperationsEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    if (class_exists(ViewsBulkOperationsEvent::class)) {
      $events[ViewsBulkOperationsEvent::NAME][] = 'provideViewData';
    }
    return $events;
  }

  /**
   * Responds to view data request events.
   *
   * @var \Drupal\views_bulk_operations\ViewsBulkOperationsEvent $event
   *   The event to respond to.
   */
  public function provideViewData(ViewsBulkOperationsEvent $event) {
    $base_table = $event->getView()->storage->get('base_table');
    $index = SearchApiQuery::getIndexFromTable($base_table);

    if ($index) {
      $event->setEntityTypeIds($index->getEntityTypes());

      $event->setEntityGetter([
        'callable' => [SearchApiQuery::class, 'getEntityFromRow'],
      ]);
    }
  }

}
