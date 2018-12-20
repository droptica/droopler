<?php

/**
 * @file
 * Contains \Drupal\d_content\Routing\ContactAlterRouteSubscriber.
 */

namespace Drupal\d_content\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class ContactAlterRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Remove the /contact route.
    $collection->remove('contact.site_page');
  }

}
