<?php

namespace Drupal\d_p;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides base plugin implementation for paragraph setting plugins.
 *
 * @package Drupal\d_p
 */
abstract class ParagraphSettingPluginBase extends PluginBase implements ParagraphSettingInterface {

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    return $this->pluginDefinition['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
    return [
      '#title' => $this->label(),
      "#default_value" => $this->getDefaultValue(),
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
    return $this->getSettings()['label'] ?? NULL;
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
  public function isSubtype(): bool {
    return $this->hasParentPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->getSettings()['weight'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedBundles(): array {
    return $this->getSettings()['allowed_bundles'] ?? [self::ALL_ALLOWED_BUNDLES];
  }

  /**
   * {@inheritdoc}
   */
  public function isBundleAllowed(string $bundle_name): bool {
    return in_array($bundle_name, $this->getAllowedBundles());
  }

  /**
   * {@inheritdoc}
   */
  public function isAllBundlesAllowed(): bool {
    return $this->isBundleAllowed(self::ALL_ALLOWED_BUNDLES);
  }

  /**
   * {@inheritdoc}
   */
  public function getValidationRulesDefinition(): array {
    return [];
  }

}
