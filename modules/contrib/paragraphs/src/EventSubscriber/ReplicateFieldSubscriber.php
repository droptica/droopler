<?php

namespace Drupal\paragraphs\EventSubscriber;

use Drupal\replicate\Events\ReplicateEntityFieldEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Drupal\replicate\Replicator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that handles cloning through the Replicate module.
 */
class ReplicateFieldSubscriber implements EventSubscriberInterface {

  /**
   * The replicator service.
   *
   * @var \Drupal\replicate\Replicator
   */
  protected $replicator;

  /**
   * ReplicateFieldSubscriber constructor.
   *
   * @param \Drupal\replicate\Replicator $replicator
   *   The replicator service.
   */
  public function __construct(Replicator $replicator) {
    $this->replicator = $replicator;
  }

  /**
   * Replicates paragraphs when the parent entity is being replicated.
   *
   * @param \Drupal\replicate\Events\ReplicateEntityFieldEvent $event
   */
  public function onClone(ReplicateEntityFieldEvent $event) {
    $field_item_list = $event->getFieldItemList();
    if ($field_item_list->getItemDefinition()->getSetting('target_type') == 'paragraph') {
      foreach ($field_item_list as $field_item) {
        $field_item->entity = $this->replicator->replicateEntity($field_item->entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ReplicatorEvents::replicateEntityField('entity_reference_revisions')][] = 'onClone';
    return $events;
  }

}
