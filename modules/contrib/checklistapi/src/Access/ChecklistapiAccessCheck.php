<?php

namespace Drupal\checklistapi\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * An access check service for checklist routes.
 */
class ChecklistapiAccessCheck implements AccessInterface {

  /**
   * Checks routing access for the checklist.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns an access result.
   */
  public function access(RouteMatchInterface $route_match) {
    $op = $route_match->getParameter('op') ?: 'any';
    $id = $route_match->getParameter('checklist_id');

    if (!$id) {
      return AccessResult::neutral();
    }

    return AccessResult::allowedIf(checklistapi_checklist_access($id, $op));
  }

}
