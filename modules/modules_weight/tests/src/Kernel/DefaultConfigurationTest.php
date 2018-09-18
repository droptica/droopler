<?php

namespace Drupal\Tests\modules_weight\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the module configurations.
 *
 * @group modules_weight
 */
class DefaultConfigurationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['modules_weight'];

  /**
   * Tests the default configuration values.
   */
  public function testDefaultConfigurationValues() {
    // Installing the configuration file.
    $this->installConfig(self::$modules);
    // Getting the config factory service.
    $config_factory = $this->container->get('config.factory');
    // Getting variable.
    $show_system_modules = $config_factory->get('modules_weight.settings')->get('show_system_modules');
    // Checking that the configuration variable is FALSE.
    $this->assertFalse($show_system_modules, 'The default configuration value for show_system_modules should be FALSE.');
  }

}
