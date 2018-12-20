<?php

namespace Drupal\facets;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Config subscriber for facet delete.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Drupal core's block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Create an instance of the class.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Core's block manager.
   */
  public function __construct(BlockManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * Reacts to a config delete event to clear the required caches.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The config delete event.
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if (strpos($config->getName(), 'facets') !== FALSE) {
      $this->blockManager->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::DELETE][] = ['onConfigDelete', 50];
    return $events;
  }

}
