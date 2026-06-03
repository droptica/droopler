<?php

declare(strict_types=1);

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
   * Main setting component used to build the form containing all of the
   * settings.
   *
   * @return array
   *   Form element.
   */
  public function formElement(): array;

  /**
   * Getter for plugin id.
   */
  public function id(): string;

  /**
   * Getter for plugin label.
   */
  public function label(): ?TranslatableMarkup;

  /**
   * Getter for form element #default_value.
   *
   * @return mixed
   *   Value to be used as a default.
   */
  public function getDefaultValue(): mixed;

  /**
   * Getter for parent plugin id.
   *
   * @return string|null
   *   The plugin id or NULL if this is a root element.
   */
  public function getParentPluginId(): ?string;

  /**
   * Check if the plugin has a parent.
   */
  public function hasParentPlugin(): bool;

  /**
   * Check if the given plugin is the plugin parent.
   */
  public function isPluginParent(string $parent_id): bool;

  /**
   * Alias of hasParentPlugin().
   */
  public function isSubtype(): bool;

  /**
   * Load all children plugins.
   *
   * @return array<string, \Drupal\d_p\ParagraphSettingInterface>
   *   Child plugin instances keyed by plugin id.
   */
  public function getChildrenPlugins(): array;

  /**
   * Getter for plugin weight, used as #weight in form element.
   */
  public function getWeight(): int;

  /**
   * Getter for validation rules definition.
   */
  public function getValidationRulesDefinition(): array;

}
