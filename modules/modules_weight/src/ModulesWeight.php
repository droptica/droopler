<?php

namespace Drupal\modules_weight;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ModulesWeight.
 *
 * @package Drupal\modules_weight
 */
class ModulesWeight implements ModulesWeightInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ModulesWeight object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getModulesList($show_core_modules = FALSE) {
    $modules = [];
    // Getting the module list.
    $installed_modules = system_get_info('module');
    // Getting the modules weight from the config.
    $modules_weight = $this->configFactory->get('core.extension')->get('module');
    // Iterating over each module.
    foreach ($installed_modules as $filename => $module_info) {
      // We don't want to show the hidden modules, or the Core modules
      // (if the $force is set to FALSE).
      if (!isset($module_info['hidden']) && ($show_core_modules || $module_info['package'] != 'Core')) {
        $modules[$filename]['name'] = $module_info['name'];
        $modules[$filename]['description'] = $module_info['description'];
        $modules[$filename]['weight'] = $modules_weight[$filename];
        $modules[$filename]['package'] = $module_info['package'];
      }
    }
    // Sorting all modules by their weight.
    uasort($modules, ['Drupal\modules_weight\Utility\SortArray', 'sortByWeightAndName']);

    return $modules;
  }

}
