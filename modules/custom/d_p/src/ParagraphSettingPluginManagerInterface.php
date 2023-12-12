<?php

declare(strict_types = 1);

namespace Drupal\d_p;

/**
 * Provides interface for paragraph setting plugin manager.
 */
interface ParagraphSettingPluginManagerInterface {

  const SETTINGS_FORM_STORAGE_CID = 'paragraph_setting_plugins:settings_form';

  const SETTINGS_SUBTYPE_ID = 'modifiers';

  /**
   * Getter for all plugin instances.
   *
   * @return array
   *   Plugin instances.
   */
  public function getAll(): array;

  /**
   * Gets plugin instance by id.
   *
   * This is a simple wrapper for createInstance method.
   * It is possible that we will load plugin configuration
   * automatically by bundle in some future version.
   * In that case we don't want to use the createInstance method
   * directly.
   *
   * @param string $plugin_id
   *   Plugin id.
   *
   * @return object|\Drupal\d_p\ParagraphSettingInterface
   *   Instance of paragraph setting plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPluginById(string $plugin_id);

  /**
   * Load all children plugins by given plugin id.
   *
   * @param string $parent_plugin_id
   *   Parent plugin id.
   *
   * @return array
   *   Plugin instances.
   */
  public function getAllChildrenPlugins(string $parent_plugin_id): array;

  /**
   * Getter for settings form built from all plugin instances.
   *
   * @param array $plugins_settings
   *   Array of plugins settings to be applied to the form element.
   *
   * @return array
   *   Form elements.
   */
  public function getSettingsForm(array $plugins_settings = []): array;

  /**
   * Getter for settings form available options built from all plugin instances.
   *
   * @return array
   *   An array containing all plugin names and its subplugins, keyed by id.
   */
  public function getSettingsFormOptions(): array;

}
