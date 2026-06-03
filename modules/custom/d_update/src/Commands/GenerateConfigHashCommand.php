<?php

declare(strict_types=1);

namespace Drupal\d_update\Commands;

use Drupal\d_update\ConfigCompareInterface;
use Drush\Commands\DrushCommands;
use Drush\Drush;

/**
 * Drush command exposing `ConfigCompare::generateHashFromDatabase()`.
 *
 * Useful when an integrator needs to capture a hash of the current
 * configuration to embed in an update hook.
 */
class GenerateConfigHashCommand extends DrushCommands {

  public function __construct(
    protected readonly ConfigCompareInterface $configCompare,
  ) {
    parent::__construct();
  }

  /**
   * Generates a config hash for the given config name.
   *
   * @param string $config_name
   *   Configuration name.
   *
   * @command generate-config-hash
   * @aliases gch
   * @usage generate-config-hash core.extension
   *   Generates hash for core.extension config.
   */
  public function generate(string $config_name): void {
    $hash = $this->configCompare->generateHashFromDatabase($config_name);
    Drush::output()->writeln(
      dt('Generated hash for config @config_name: @hash', [
        '@config_name' => $config_name,
        '@hash' => $hash === FALSE ? '<no config found>' : $hash,
      ]),
    );
  }

}
