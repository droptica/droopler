<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Psr\Log\LoggerInterface;

/**
 * The plugin manager for paragraph settings plugins.
 */
class ParagraphSettingPluginManager extends DefaultPluginManager implements ParagraphSettingPluginManagerInterface {

  use LoggerChannelTrait;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

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
    $this->setCacheBackend($cache_backend, 'paragraph_setting_plugins');
    $this->logger = $this->getLogger('d_p');
  }

  /**
   * {@inheritdoc}
   */
  public function getAll(): array {
    return $this->loadPluginsFromDefinitions($this->getDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginById(string $plugin_id) {
    return $this->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllChildrenPlugins(string $parent_plugin_id): array {
    $definitions = [];

    foreach ($this->getDefinitions() as $definition) {
      if (isset($definition['settings']['parent']) && $definition['settings']['parent'] === $parent_plugin_id) {
        $definitions[] = $definition;
      }
    }

    return $this->loadPluginsFromDefinitions($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $plugins_settings = []): array {
    $cid = self::SETTINGS_FORM_STORAGE_CID . ':' . md5(serialize($plugins_settings));
    $cache = $this->cacheGet($cid);

    if ($cache) {
      $form = $cache->data;
    }
    else {
      /** @var \Drupal\d_p\ParagraphSettingInterface[] $plugins */
      $plugins = $this->getAll();
      $form = [];

      foreach ($plugins as $plugin) {
        if (!$plugin->isSubtype()) {
          $form[$plugin->id()] = $plugin->formElement($plugins_settings[$plugin->id()] ?? []);
        }
      }

      foreach ($plugins as $plugin) {
        if ($plugin->isSubtype()) {
          $form[$plugin->getParentPluginId()][self::SETTINGS_SUBTYPE_ID][$plugin->id()] = $plugin->formElement();
        }
      }

      $this->moduleHandler->alter('d_settings', $form);

      $this->cacheSet($cid, $form, Cache::PERMANENT, [self::SETTINGS_FORM_STORAGE_CID]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsFormOptions(): array {
    $options = [];

    foreach ($this->getSettingsForm() as $id => $element) {
      $options[$id] = [
        'label' => $element['#title'],
        'plugin' => $element['#plugin'],
      ];
      $modifiers = self::SETTINGS_SUBTYPE_ID;
      if (isset($element[$modifiers])) {
        foreach ($element[$modifiers] as $mid => $modifier) {
          $options[$id][$modifiers][$mid]['label'] = $modifier['#title'];
        }
      }
    }

    $this->sortSettingsOptions($options);

    return $options;
  }

  /**
   * Load all plugins by given definitions.
   *
   * @param array $definitions
   *   Plugin definitions.
   *
   * @return array
   *   Loaded plugin instances.
   */
  protected function loadPluginsFromDefinitions(array $definitions): array {
    $plugins = [];

    foreach ($definitions as $definition) {
      try {
        // @todo We can think of keeping the configuration in yml files.
        $plugins[$definition['id']] = $this->getPluginById($definition['id']);
      }
      catch (PluginException $exception) {
        $this->logger->error($exception->getMessage());
      }
    }

    return $plugins;
  }

  /**
   * Provides alphabetic sorting for settings options.
   *
   * @param array $options
   *   Settings options.
   */
  protected function sortSettingsOptions(array &$options): void {
    uasort($options, function ($a, $b) {
      return $a['label'] <=> $b['label'];
    });

    foreach ($options as &$option) {
      if (isset($option[self::SETTINGS_SUBTYPE_ID])) {
        $this->sortSettingsOptions($option[self::SETTINGS_SUBTYPE_ID]);
      }
    }
  }

}
