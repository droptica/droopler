<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides interface for paragraph setting plugins.
 */
interface ParagraphSettingInterface {

  /**
   * Plugin settings.
   *
   * @return array
   *   List of settings.
   */
  public function getSettings(): array;

  /**
   * Paragraph setting form element.
   *
   * This is a main setting component used, to build the form
   * contain all the settings.
   *
   * @return array
   *   Form element.
   */
  public function formElement(array $settings = []): array;

  /**
   * Getter for plugin id.
   *
   * @return string
   *   The plugin id.
   */
  public function id(): string;

  /**
   * Getter for plugin label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The plugin label.
   */
  public function label(): ?TranslatableMarkup;

  /**
   * Getter for form element #default_value.
   *
   * @return mixed
   *   Value to be used as a default
   */
  public function getDefaultValue();

  /**
   * Getter for parent plugin id.
   *
   * @return string|null
   *   The plugin id or null if this is a root element.
   */
  public function getParentPluginId(): ?string;

  /**
   * Check if the plugin has parent.
   *
   * @return bool
   *   True if has parent, false if this is a root element.
   */
  public function hasParentPlugin(): bool;

  /**
   * Check if the given plugin is the plugin parent.
   *
   * @param string $parent_id
   *   Id of plugin parent to be checked.
   *
   * @return bool
   *   True if this is the plugin parent, false otherwise.
   */
  public function isPluginParent(string $parent_id): bool;

  /**
   * This is an alias of hasParentPlugin.
   *
   * @return bool
   *   True if has parent, false if this is a root element.
   */
  public function isSubtype(): bool;

  /**
   * Load all children plugins.
   *
   * @return array
   *   Child plugin instances.
   */
  public function getChildrenPlugins(): array;

  /**
   * Getter for plugin weight, used as #weight in form element.
   *
   * @return int
   *   Plugin weight.
   */
  public function getWeight(): int;

  /**
   * Getter for validation rules definition.
   *
   * @return array
   *   Validation rules.
   */
  public function getValidationRulesDefinition(): array;

  /**
   * Get plugin settings form.
   *
   * @param array $values
   *   The existing plugin settings default values.
   *
   * @return array
   *   The plugin settings form.
   */
  public function getPluginSettingsForm(array $values = []): array;

}
