<?php

declare(strict_types = 1);

namespace Drupal\d_update\Commands;

use Drupal\d_update\ConfigCompareInterface;
use Drush\Commands\DrushCommands;
use Drush\Drush;

/**
 * Drush command for config hash generation.
 */
class GenerateConfigHashCommand extends DrushCommands {

  /**
   * Config compare service.
   *
   * @var \Drupal\d_update\ConfigCompareInterface
   */
  protected $configCompare;

  /**
   * Generate config hash command constructor.
   *
   * @param \Drupal\d_update\ConfigCompareInterface $config_compare
   *   Config compare service.
   */
  public function __construct(ConfigCompareInterface $config_compare) {
    parent::__construct();

    $this->configCompare = $config_compare;
  }

  /**
   * Generates config hash for given config name.
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
        '@hash' => $hash,
      ]));
  }

}
