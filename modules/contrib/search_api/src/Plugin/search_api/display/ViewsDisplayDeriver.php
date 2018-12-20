<?php

namespace Drupal\search_api\Plugin\search_api\display;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\search_api\Display\DisplayDeriverBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\ViewEntityInterface;

/**
 * Derives a display plugin definition for all supported search view displays.
 *
 * @see \Drupal\search_api\Plugin\search_api\display\ViewsBlock
 * @see \Drupal\search_api\Plugin\search_api\display\ViewsPage
 * @see \Drupal\search_api\Plugin\search_api\display\ViewsRest
 */
class ViewsDisplayDeriver extends DisplayDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $this->derivatives = [];

      try {
        /** @var \Drupal\Core\Entity\EntityStorageInterface $views_storage */
        $views_storage = $this->entityTypeManager->getStorage('view');
        $all_views = $views_storage->loadMultiple();
      }
      catch (PluginNotFoundException $e) {
        return $this->derivatives;
      }

      /** @var \Drupal\views\Entity\View $view */
      foreach ($all_views as $view) {
        $this->derivatives += $this->getDisplaysForView($base_plugin_definition, $view, $this->derivatives);
      }
    }

    return $this->derivatives;
  }

  /**
   * Creates derived plugin definitions for a view.
   *
   * @param array $base_plugin_definition
   *   The plugin definition for this plugin.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view to create plugin definitions for.
   * @param array $plugin_derivatives
   *   An array of already existing derived plugin definitions.
   *
   * @return array
   *   Returns an array of plugin definitions, keyed by derivative ID.
   */
  protected function getDisplaysForView(array $base_plugin_definition, ViewEntityInterface $view, array $plugin_derivatives) {
    $type = $base_plugin_definition['views_display_type'];

    $index = SearchApiQuery::getIndexFromTable($view->get('base_table'));
    if (!$index instanceof IndexInterface) {
      return [];
    }

    $displays = $view->get('display');
    foreach ($displays as $name => $display_info) {
      if ($display_info['display_plugin'] == $type) {
        // Create a machine name by getting the view ID and appending the name
        // of the display to it (block1, rest_export, foobar).
        $base_machine_name = $view->id() . '__' . $name;
        $machine_name = $base_machine_name;

        // Make sure the machine name is unique. (Will almost always be
        // the case, unless a view or page ID contains two consecutive
        // underscores.)
        $i = 0;
        while (isset($plugin_derivatives[$machine_name])) {
          $machine_name = $base_machine_name . '_' . ++$i;
        }

        $label_arguments = [
          '%view_name' => $view->label(),
          '%display_title' => $display_info['display_title'],
        ];
        $label = $this->t('View %view_name, display %display_title', $label_arguments);

        $executable = $view->getExecutable();
        $executable->setDisplay($name);
        $display = $executable->getDisplay();

        // Create the actual derivative plugin definition.
        $args = [
          '%view_name' => $view->label(),
          '%display_title' => $display_info['display_title'],
        ];
        if ($view->get('description')) {
          $args['%view_description'] = $view->get('description');
          $description = $this->t('%view_description – Represents the display %display_title of view %view_name.', $args);
        }
        else {
          $description = $this->t('Represents the display %display_title of view %view_name.', $args);
        }
        $plugin_derivatives[$machine_name] = [
          'label' => $label,
          'description' => $description,
          'view_id' => $view->id(),
          'view_display' => $name,
          'index' => $index->id(),
        ] + $base_plugin_definition;

        // Add the path information to the definition.
        if ($display->hasPath()) {
          $plugin_derivatives[$machine_name]['path'] = '/' . $display->getPath();
        }
      }
    }

    return $plugin_derivatives;
  }

}
