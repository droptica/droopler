<?php

namespace Drupal\d_content_init\Commands;

use Drush\Commands\DrushCommands;

/**
 * A drush command file.
 *
 * @package Drupal\drush9_custom_commands\Commands
 */
class DContentInitCommands extends DrushCommands {

  /**
   * Removes all the content and recreates it again.
   *
   * @command recreate-content
   * @usage recreate-content
   */
  public function recreate_content() {
    d_content_init_recreate_content();
  }

}
