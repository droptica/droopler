<?php

namespace Drupal\d_p;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The plugin manager for paragraph settiings plugins.
 *
 * @package Drupal\d_p
 */
class ParagraphSettingPluginManager extends DefaultPluginManager implements ParagraphSettingPluginManagerInterface {
  use LoggerChannelTrait;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ParagraphSetting',
      $namespaces,
      $module_handler,
      'Drupal\d_p\ParagraphSettingInterface',
      'Drupal\d_p\Annotation\ParagraphSetting'
    );

    $this->alterInfo('paragraph_setting_info');
    $this->setCacheBackend($cache_backend, 'paragraph_setting_info_plugins');
    $this->logger = $this->getLogger('d_p');
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Rework or add method to load all plugins by pragraph bundle.
   */
  public function getAll(): array {
    $plugins = [];
    foreach ($this->getDefinitions() as $definition) {
      try {
        // @todo: We can think of keeping the configuration in yml files.
        $plugins[$definition['id']] = $this->createInstance($definition['id'], []);
      }
      catch (PluginException $exception) {
        $this->logger->error($exception->getMessage());
      }
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(): array {
    /** @var \Drupal\d_p\ParagraphSettingInterface[] $plugins */
    $plugins = $this->getAll();
    $form = [];

    foreach ($plugins as $plugin) {
      if (!$plugin->isSubtype()) {
        $form[$plugin->id()] = $plugin->formElement();
      }
    }

    foreach ($plugins as $plugin) {
      if ($plugin->isSubtype()) {
        $form[$plugin->getParentPluginId()]['modifiers'][$plugin->id()] = $plugin->formElement();
      }
    }

    return $form;
  }

}
