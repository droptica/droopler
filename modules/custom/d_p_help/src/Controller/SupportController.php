<?php

namespace Drupal\d_p_help\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for help routes.
 */
class SupportController extends ControllerBase {

  /**
   * Prints a page about Droopler distribution.
   *
   * @return array
   *   A render array for the info page.
   */
  public function render() {
    $output = '<h3>' . t('Droopler is a Drupal 8 profile designed to kickstart a new webpage in a few minutes') . '</h3>';
    $output .= '<p>' . t('More info about Droopler - <a href=":link">See official Droopler website</a>.', [':link' => 'https://droopler.com/']) . '</p>';
    $output .= '<h3>' . t('Support') . '</h3>';
    $output .= '<p>' . t('Do You need support with Droopler? -  <a href=":link">Droptica.com</a>.', [':link' => 'https://droptica.com']) . '</p>';
    $output .= '<h3>' . t('Github') . '</h3>';
    $output .= '<p>' . t('<a href=":link">https://github.com/droptica/droopler_project</a> - Boilerplate for new projects based on Droopler. If you wish to use Droopler - fork (or download) this repository. It contains a minimum set of code to start your new website.', [':link' => 'https://github.com/droptica/droopler_project']) . '</p>';
    $output .= '<p>' . t('<a href=":link">https://github.com/droptica/droopler</a> - This is Drupal installation profile.', [':link' => 'https://github.com/droptica/droopler']) . '</p>';
    return [
      '#type' => 'markup',
      '#markup' => '<div class="container">' . $output . '</div>',
    ];
  }
}
