<?php

declare(strict_types = 1);

namespace Drupal\d_frontend_editing\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\d_frontend_editing\Controller\FrontendEditingController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines a route subscriber for the Frontend Editing module.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('frontend_editing.paragraph_add_page')) {
      $route->setDefaults([
        '_controller' => FrontendEditingController::class . '::paragraphAddPage',
      ]);
    }
  }

}
