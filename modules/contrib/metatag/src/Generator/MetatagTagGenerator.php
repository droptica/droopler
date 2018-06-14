<?php

namespace Drupal\metatag\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\TwigRenderer;

/**
 * Drupal Console plugin for generating a tag.
 */
class MetatagTagGenerator extends Generator {

  /**
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * @var \Drupal\Console\Core\Utils\TwigRenderer
   */
  protected $render;

  /**
   * MetatagTagGenerator constructor.
   *
   * @param Drupal\Console\Extension\Manager $extensionManager
   * @param Drupal\Console\Core\Utils\TwigRenderer $render
   */
  public function __construct(Manager $extensionManager, TwigRenderer $render) {
    $this->extensionManager = $extensionManager;

    $render->addSkeletonDir(__DIR__ . '/../../templates/');
    $this->setRenderer($render);
  }

  /**
   * Generator plugin.
   *
   * @param string $base_class
   * @param string $module
   * @param string $name
   * @param string $label
   * @param string $description
   * @param string $plugin_id
   * @param string $class_name
   * @param string $group
   * @param string $weight
   * @param string $type
   * @param bool $secure
   * @param bool $multiple
   */
  public function generate($base_class, $module, $name, $label, $description, $plugin_id, $class_name, $group, $weight, $type, $secure, $multiple) {
    $parameters = [
      'base_class' => $base_class,
      'module' => $module,
      'name' => $name,
      'label' => $label,
      'description' => $description,
      'plugin_id' => $plugin_id,
      'class_name' => $class_name,
      'group' => $group,
      'weight' => $weight,
      'type' => $type,
      'secure' => $secure,
      'multiple' => $multiple,
      'prefix' => '<' . '?php',
    ];

    $this->renderFile(
      'tag.php.twig',
      $this->extensionManager->getPluginPath($module, 'metatag/Tag') . '/' . $class_name . '.php',
      $parameters
    );

    $this->renderFile(
      'metatag_tag.schema.yml.twig',
      $this->extensionManager->getModule($module)->getPath() . '/config/schema/' . $module . '.metatag_tag.schema.yml',
      $parameters,
      FILE_APPEND
    );
  }

}
