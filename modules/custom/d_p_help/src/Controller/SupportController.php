<?php

namespace Drupal\d_p_help\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for help routes.
 */
class SupportController extends ControllerBase {

  /**
   * Prints a page listing various types of help.
   *
   * The page has sections defined by \Drupal\help\HelpSectionPluginInterface
   * plugins.
   *
   * @return array
   *   A render array for the help page.
   */
  public function render() {

    return [
      '#type' => 'markup',
      '#markup' => '<div class="container">' . t('Hello World!') . '</div>',
    ];
  }
}
