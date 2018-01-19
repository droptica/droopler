<?php
/**
 * @file
 * Contains \Drupal\my_module\Routing\RouteSubscriber.
 */

namespace Drupal\d_blog\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

    /**
     * {@inheritdoc}
     */
    public function alterRoutes(RouteCollection $collection) {
        if ($route = $collection->get('entity.taxonomy_term.canonical')) {
            $route->setDefault('_controller', '\Drupal\d_blog\Controller\TaxonomyTermViewPageController::handle');
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        $events = parent::getSubscribedEvents();

        // Come after views.
        $events[RoutingEvents::ALTER] = array('onAlterRoutes', -180);

        return $events;
    }

}