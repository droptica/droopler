<?php

namespace Drupal\d_content\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 *
 * Releases '/contact' path originally taken by Drupal default webform
 * for custom contact form, which path without altering would be '/contact-0'.
 */
class ContactAlterRouteSubscriber extends RouteSubscriberBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ContactAlterRouteSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Remove the /contact route.
    if ($collection->get('contact.site_page')) {
      $collection->remove('contact.site_page');
    }

    if (!($this->moduleHandler->moduleExists('contact'))) {
      if ($collection->get('entity.user.contact_form')) {
        $collection->remove('entity.user.contact_form');
      }
    }
  }

}
