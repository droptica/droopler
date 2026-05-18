<?php

declare(strict_types=1);

namespace Drupal\d_p;

/**
 * Provides interface for the paragraph setting plugin manager.
 */
interface ParagraphSettingPluginManagerInterface {

  public const string SETTINGS_FORM_STORAGE_CID = 'paragraph_setting_plugins:settings_form';

  public const string SETTINGS_SUBTYPE_ID = 'modifiers';

  /**
   * Getter for all plugin instances.
   *
   * @return array<string, \Drupal\d_p\ParagraphSettingInterface>
   *   Plugin instances keyed by plugin id.
   */
  public function getAll(): array;

  /**
   * Gets a plugin instance by id.
   *
   * Simple wrapper for createInstance(); kept separate so future versions can
   * load plugin configuration automatically by bundle without changing
   * consumers.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPluginById(string $plugin_id): ParagraphSettingInterface;

  /**
   * Load all children plugins by parent plugin id.
   *
   * @return array<string, \Drupal\d_p\ParagraphSettingInterface>
   *   Children plugins keyed by their plugin id.
   */
  public function getAllChildrenPlugins(string $parent_plugin_id): array;

  /**
   * Settings form built from all plugin instances.
   */
  public function getSettingsForm(): array;

  /**
   * Settings form available options built from all plugin instances.
   *
   * @return array<string, array{label: mixed, modifiers?: array<string, array{label: mixed}>}>
   *   Plugin names with their subplugins, keyed by id.
   */
  public function getSettingsFormOptions(): array;

}
