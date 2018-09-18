<?php

namespace Drupal\Tests\modules_weight\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\modules_weight\ModulesWeight;

/**
 * Tests the ModulesWeight class methods.
 *
 * @group modules_weight
 * @coversDefaultClass \Drupal\modules_weight\ModulesWeight
 */
class ModulesWeightTest extends UnitTestCase {

  /**
   * Tests the modules list with ModulesWeight::getModulesList().
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param bool $show_core_modules
   *   Force to show the core modules.
   *
   * @covers ::getModulesList
   * @dataProvider providerGetModulesList
   */
  public function testGetModulesList($expected, $show_core_modules) {

    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');

    $weight = [
      'standard' => 1000,
      'admin_toolbar' => 3,
      'modules_weight' => -5,
      'views' => 0,
    ];

    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('module')
      ->willReturn($weight);

    // Config factory mock.
    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');

    // ConfigFactoryInterface::get mock.
    $config_factory->expects($this->any())
      ->method('get')
      ->with('core.extension')
      ->willReturn($config);

    // Creating the object.
    $modules_weight = new ModulesWeight($config_factory);
    // Testing the function.
    $this->assertSame($expected, $modules_weight->getModulesList($show_core_modules));
  }

  /**
   * Data provider for testGetModulesList().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getLanguageLabel().
   *   - 'show_core_modules' - Force to show the core modules.
   *
   * @see testGetLanguageLabel()
   */
  public function providerGetModulesList() {

    // Show core modules.
    $show_core_modules['modules_weight'] = [
      'name' => 'Modules Weight',
      'description' => 'Allows to change the modules execution order.',
      'weight' => -5,
      'package' => 'Development',
    ];

    $show_core_modules['views'] = [
      'name' => 'Views',
      'description' => 'Create customized lists and queries from your database.',
      'weight' => 0,
      'package' => 'Core',
    ];

    $show_core_modules['admin_toolbar'] = [
      'name' => 'Admin Toolbar',
      'description' => 'Provides a drop-down menu interface to the core Drupal Toolbar.',
      'weight' => 3,
      'package' => 'Administration',
    ];

    // Not show core modules.
    $not_show_core_modules['modules_weight'] = [
      'name' => 'Modules Weight',
      'description' => 'Allows to change the modules execution order.',
      'weight' => -5,
      'package' => 'Development',
    ];

    $not_show_core_modules['admin_toolbar'] = [
      'name' => 'Admin Toolbar',
      'description' => 'Provides a drop-down menu interface to the core Drupal Toolbar.',
      'weight' => 3,
      'package' => 'Administration',
    ];

    $tests['show core modules'] = [$show_core_modules, TRUE];
    $tests['not show core modules'] = [$not_show_core_modules, FALSE];

    return $tests;
  }

}

namespace Drupal\modules_weight;

/**
 * Shadow system_get_info() system call.
 *
 * @param string $type
 *   Either 'module' or 'theme'.
 *
 * @return array
 *   An associative array of module or theme information keyed by name, or only
 *   information for $name, if given. If no records are available, an empty
 *   array is returned.
 */
function system_get_info($type) {

  $modules['admin_toolbar'] = [
    'name' => 'Admin Toolbar',
    'description' => 'Provides a drop-down menu interface to the core Drupal Toolbar.',
    'package' => 'Administration',
  ];

  $modules['standard'] = [
    'name' => 'Standard',
    'description' => 'Install with commonly used features pre-configured.',
    'package' => 'Other',
    'hidden' => 1,
  ];

  $modules['views'] = [
    'name' => 'Views',
    'description' => 'Create customized lists and queries from your database.',
    'package' => 'Core',
  ];

  $modules['modules_weight'] = [
    'name' => 'Modules Weight',
    'description' => 'Allows to change the modules execution order.',
    'package' => 'Development',
  ];

  return $modules;
}
