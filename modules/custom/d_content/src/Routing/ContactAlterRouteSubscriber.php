<?php

namespace Drupal\d_content\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 * Releases '/contact' path originally taken by Drupal default webform
 * for custom contact form, which path without altering would be '/contact-0'.
 */
class ContactAlterRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Remove the /contact route.
    if ($route = $collection->get('contact.site_page')) {
      $collection->remove('contact.site_page');
    }

    $moduleHandler = \Drupal::service('module_handler');
    if (!($moduleHandler->moduleExists('contact'))) {
      if ($route = $collection->get('entity.user.contact_form')) {
        $collection->remove('entity.user.contact_form');
      }
    }
  }

}
