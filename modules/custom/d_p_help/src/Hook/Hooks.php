<?php

declare(strict_types=1);

namespace Drupal\d_p_help\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the d_p_help module.
 */
class Hooks {

  use StringTranslationTrait;

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    $page['#attached']['library'][] = 'd_p_help/droopler';
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): string {
    if ($route_name !== 'help.page.d_p_help') {
      return '';
    }

    $output = '<h3>' . $this->t('Droopler is a Drupal 11 profile designed to kickstart a new webpage in a few minutes') . '</h3>';
    $output .= '<p>' . $this->t('More info about Droopler - <a href=":link">See official Droopler website</a>.', [':link' => 'https://droopler.com/']) . '</p>';
    $output .= '<h3>' . $this->t('Support') . '</h3>';
    $output .= '<p>' . $this->t('Do You need support with Droopler? -  <a href=":link">Droptica.com</a>.', [':link' => 'https://droptica.com']) . '</p>';
    $output .= '<h3>' . $this->t('Github') . '</h3>';
    $output .= '<p>' . $this->t('<a href=":link">https://github.com/droptica/droopler_project</a> - Boilerplate for new projects based on Droopler. If you wish to use Droopler - fork (or download) this repository. It contains a minimum set of code to start your new website.', [':link' => 'https://github.com/droptica/droopler_project']) . '</p>';
    $output .= '<p>' . $this->t('<a href=":link">https://github.com/droptica/droopler</a> - This is Drupal installation profile.', [':link' => 'https://github.com/droptica/droopler']) . '</p>';

    return $output;
  }

}
