<?php

declare(strict_types = 1);

namespace Drupal\d_p;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base plugin implementation for paragraph setting plugins.
 */
abstract class ParagraphSettingPluginBase extends PluginBase implements ParagraphSettingInterface, ContainerFactoryPluginInterface {


  /**
   * Paragraph setting plugin manager.
   *
   * @var \Drupal\d_p\ParagraphSettingPluginManagerInterface
   */
  protected ParagraphSettingPluginManagerInterface $settingManager;

  /**
   * Constructs paragraph plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\d_p\ParagraphSettingPluginManagerInterface $setting_manager
   *   Paragraph setting plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ParagraphSettingPluginManagerInterface $setting_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->settingManager = $setting_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('d_p.paragraph_settings.plugin.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    return $this->pluginDefinition['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    return [
      '#title' => $this->label(),
      '#default_value' => $this->getDefaultValue(),
      '#weight' => $this->getWeight(),
      '#plugin' => $this,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return parent::getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function label(): ?TranslatableMarkup {
    return $this->pluginDefinition['label'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentPluginId(): ?string {
    return $this->getSettings()['parent'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasParentPlugin(): bool {
    return is_string($this->getParentPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function isPluginParent(string $parent_id): bool {
    return $this->getParentPluginId() === $parent_id;
  }

  /**
   * {@inheritdoc}
   */
  public function isSubtype(): bool {
    return $this->hasParentPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getChildrenPlugins(): array {
    return $this->settingManager->getAllChildrenPlugins($this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->getSettings()['weight'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidationRulesDefinition(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginSettingsForm(array $values = []): array {
    return [];
  }

}
