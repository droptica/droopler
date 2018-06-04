<?php

namespace Drupal\metatag\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\TwigRenderer;

/**
 * Drupal Console plugin for generating a group.
 */
class MetatagGroupGenerator extends Generator {

  /**
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * @var \Drupal\Console\Core\Utils\TwigRenderer
   */
  protected $renderer;

  /**
   * MetatagGroupGenerator constructor.
   *
   * @param Drupal\Console\Extension\Manager $extensionManager
   * @param Drupal\Console\Core\Utils\TwigRenderer $renderer
   */
  public function __construct(Manager $extensionManager, TwigRenderer $renderer) {
    $this->extensionManager = $extensionManager;

    $renderer->addSkeletonDir(__DIR__ . '/../../templates/');
    $this->setRenderer($renderer);
  }

  /**
   * Generator plugin.
   *
   * @param string $base_class
   * @param string $module
   * @param string $label
   * @param string $description
   * @param string $plugin_id
   * @param string $class_name
   * @param string $weight
   */
  public function generate($base_class, $module, $label, $description, $plugin_id, $class_name, $weight) {
    $parameters = [
      'base_class' => $base_class,
      'module' => $module,
      'label' => $label,
      'description' => $description,
      'plugin_id' => $plugin_id,
      'class_name' => $class_name,
      'weight' => $weight,
      'prefix' => '<' . '?php',
    ];

    $this->renderFile(
      'group.php.twig',
      $this->extensionManager->getPluginPath($module, 'metatag/Group') . '/' . $class_name . '.php',
      $parameters
    );
  }

}
