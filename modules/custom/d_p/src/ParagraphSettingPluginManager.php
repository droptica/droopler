<?php

declare(strict_types=1);

namespace Drupal\d_p;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\d_p\Annotation\ParagraphSetting;

/**
 * The plugin manager for paragraph settings plugins.
 */
class ParagraphSettingPluginManager extends DefaultPluginManager implements ParagraphSettingPluginManagerInterface {

  protected const string LOGGER_CHANNEL = 'd_p';

  /**
   * Logger channel for the d_p plugin manager.
   */
  protected readonly LoggerChannelInterface $logger;

  /**
   * Constructs the plugin manager.
   *
   * @param \Traversable $namespaces
   *   Root paths keyed by namespace.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend used for plugin definitions.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler used for the alter hook.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   Logger factory used to obtain the d_p channel.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    LoggerChannelFactoryInterface $logger_channel_factory,
  ) {
    parent::__construct(
      'Plugin/ParagraphSetting',
      $namespaces,
      $module_handler,
      ParagraphSettingInterface::class,
      ParagraphSetting::class,
    );

    $this->alterInfo('paragraph_setting_info');
    $this->setCacheBackend($cache_backend, 'paragraph_setting_plugins');
    $this->logger = $logger_channel_factory->get(self::LOGGER_CHANNEL);
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
  public function getPluginById(string $plugin_id): ParagraphSettingInterface {
    /** @var \Drupal\d_p\ParagraphSettingInterface $instance */
    $instance = $this->createInstance($plugin_id);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllChildrenPlugins(string $parent_plugin_id): array {
    $definitions = [];

    foreach ($this->getDefinitions() as $definition) {
      if (($definition['settings']['parent'] ?? NULL) === $parent_plugin_id) {
        $definitions[] = $definition;
      }
    }

    return $this->loadPluginsFromDefinitions($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(): array {
    $cache = $this->cacheGet(self::SETTINGS_FORM_STORAGE_CID);
    if ($cache) {
      return $cache->data;
    }

    /** @var array<string, \Drupal\d_p\ParagraphSettingInterface> $plugins */
    $plugins = $this->getAll();
    $form = [];

    foreach ($plugins as $plugin) {
      if (!$plugin->isSubtype()) {
        $form[$plugin->id()] = $plugin->formElement();
      }
    }

    foreach ($plugins as $plugin) {
      if ($plugin->isSubtype()) {
        $form[$plugin->getParentPluginId()][self::SETTINGS_SUBTYPE_ID][$plugin->id()] = $plugin->formElement();
      }
    }

    $this->moduleHandler->alter('d_settings', $form);
    $this->cacheSet(self::SETTINGS_FORM_STORAGE_CID, $form, Cache::PERMANENT, [self::SETTINGS_FORM_STORAGE_CID]);

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
      ];
      $modifiers_key = self::SETTINGS_SUBTYPE_ID;
      if (!isset($element[$modifiers_key])) {
        continue;
      }
      foreach ($element[$modifiers_key] as $modifier_id => $modifier) {
        $options[$id][$modifiers_key][$modifier_id]['label'] = $modifier['#title'];
      }
    }

    $this->sortSettingsOptions($options);

    return $options;
  }

  /**
   * Load all plugins by given definitions.
   *
   * @param array<int|string, array<string, mixed>> $definitions
   *   Plugin definitions.
   *
   * @return array<string, \Drupal\d_p\ParagraphSettingInterface>
   *   Loaded plugin instances keyed by plugin id.
   */
  protected function loadPluginsFromDefinitions(array $definitions): array {
    $plugins = [];

    foreach ($definitions as $definition) {
      try {
        // @todo Consider keeping the configuration in yml files.
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
   */
  protected function sortSettingsOptions(array &$options): void {
    uasort($options, static fn (array $a, array $b): int => $a['label'] <=> $b['label']);

    foreach ($options as &$option) {
      if (isset($option[self::SETTINGS_SUBTYPE_ID])) {
        $this->sortSettingsOptions($option[self::SETTINGS_SUBTYPE_ID]);
      }
    }
  }

}
