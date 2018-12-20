<?php

namespace Drupal\checklistapi\Routing;

use Symfony\Component\Routing\Route;

/**
 * Provides routes for checklists.
 */
class ChecklistapiRoutes {

  /**
   * Provides dynamic routes.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];
    $definitions = \Drupal::moduleHandler()->invokeAll('checklistapi_checklist_info');
    foreach ($definitions as $id => $definition) {
      // Ignore incomplete definitions.
      if (empty($definition['#path']) || empty($definition['#title'])) {
        continue;
      }

      $requirements = ['_checklistapi_access' => 'TRUE'];

      // View/edit checklist.
      $routes["checklistapi.checklists.{$id}"] = new Route($definition['#path'], [
        '_title' => (string) $definition['#title'],
        '_form' => '\Drupal\checklistapi\Form\ChecklistapiChecklistForm',
        'checklist_id' => $id,
        'op' => 'any',
      ], $requirements);

      // Clear saved progress.
      $routes["checklistapi.checklists.{$id}.clear"] = new Route("{$definition['#path']}/clear", [
        '_title' => 'Clear',
        '_form' => '\Drupal\checklistapi\Form\ChecklistapiChecklistClearForm',
        'checklist_id' => $id,
        'op' => 'edit',
      ], $requirements);
    }
    return $routes;
  }

}
