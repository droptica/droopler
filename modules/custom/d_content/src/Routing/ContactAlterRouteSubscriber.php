<?php

declare(strict_types = 1);

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
   * Constructs a ContactAlterRouteSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(protected ModuleHandlerInterface $moduleHandler) {
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
