<?php

declare(strict_types=1);

namespace Drupal\d_p;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base plugin implementation for paragraph setting plugins.
 *
 * $settingManager is intentionally not readonly: PluginBase uses
 * DependencySerializationTrait, whose __wakeup() reinjects services by
 * assigning to the property from PluginBase scope. A readonly promoted property
 * can only be initialized from its declaring class scope, so unserializing a
 * cached plugin would throw "Cannot initialize readonly property".
 */
abstract class ParagraphSettingPluginBase extends PluginBase implements ParagraphSettingInterface, ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected ParagraphSettingPluginManagerInterface $settingManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    // @phpstan-ignore-next-line Drupal uses late static binding for subclass factories.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('d_p.paragraph_settings.plugin.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    return $this->pluginDefinition['settings'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
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
  public function getDefaultValue(): mixed {
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

}
