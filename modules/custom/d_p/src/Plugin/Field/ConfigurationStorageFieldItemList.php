<?php

declare(strict_types=1);

namespace Drupal\d_p\Plugin\Field;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\d_p\ParagraphSettingInterface;
use Drupal\d_p\ParagraphSettingPluginManagerInterface;
use Drupal\d_p\ParagraphSettingSelectInterface;
use Drupal\d_p\ParagraphSettingTypesInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides the field item list class for the configuration storage field type.
 *
 * Field item lists are instantiated through Drupal's typed data manager and
 * therefore cannot use constructor-based dependency injection. Service access
 * is wrapped in lazy getters that consult the container on first use; this is
 * the Drupal-blessed exception to the "no `\Drupal::service()` in classes"
 * rule (see phpstan.neon).
 */
class ConfigurationStorageFieldItemList extends FieldItemList implements ConfigurationStorageFieldItemListInterface {

  protected const string LOGGER_CHANNEL = 'd_p';

  /**
   * Lazily resolved paragraph-setting plugin manager.
   */
  protected ?ParagraphSettingPluginManagerInterface $pluginManager = NULL;

  /**
   * Lazily resolved logger channel.
   */
  protected ?LoggerInterface $logger = NULL;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = parent::getValue();
    return $value[0] ?? new \stdClass();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $value = parent::defaultValuesFormSubmit($element, $form, $form_state);
    return $this->toEncodedValue($value);
  }

  /**
   * {@inheritdoc}
   */
  public function hasClasses(): bool {
    return $this->getClassesArrayValue() !== [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasClass($class_name): bool {
    if (is_array($class_name)) {
      foreach ($class_name as $class) {
        if ($this->hasClass($class)) {
          return TRUE;
        }
      }
      return FALSE;
    }

    return in_array((string) $class_name, $this->getClassesArrayValue(), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getClasses(): array {
    $classes = $this->getClassesArrayValue();
    $this->processDefaultClasses($classes);
    return array_values(array_unique(array_filter($classes)));
  }

  /**
   * {@inheritdoc}
   */
  public function addClass(string $value): self {
    $classes = $this->getClassesArrayValue();
    $classes[] = $value;
    $this->setClasses($classes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeClass(string $value): self {
    if (!$this->hasClass($value)) {
      return $this;
    }
    $classes = $this->getClassesArrayValue();
    $key = array_search($value, $classes, TRUE);
    if ($key !== FALSE) {
      unset($classes[$key]);
    }
    $this->setClasses($classes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceClass(string $old_value, string $new_value): self {
    if (!$this->hasClass($old_value)) {
      return $this;
    }
    $classes = $this->getClassesArrayValue();
    $key = array_search($old_value, $classes, TRUE);
    if ($key !== FALSE) {
      $classes[$key] = $new_value;
    }
    $this->setClasses($classes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setClasses(array $classes): void {
    $values = $this->getValue();
    $values->{ParagraphSettingTypesInterface::CSS_CLASS_SETTING_NAME} = implode(
      self::CSS_CLASS_DELIMITER,
      array_unique($classes),
    );
    $this->setEncodedValue($values);
  }

  /**
   * {@inheritdoc}
   */
  public function hasSettingValue(string $setting_name): bool {
    return $this->getSettingValue($setting_name) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingValue(string $setting_name, $default = NULL) {
    return $this->getValue()->$setting_name
      ?? $this->getStorageItemDefaultValue($setting_name)
      ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingValue(string $name, $value): self {
    $values = $this->getValue();
    $values->$name = $value;
    $this->setEncodedValue($values);
    return $this;
  }

  /**
   * Raw classes value from the stored configuration.
   */
  protected function getClassesValue(): mixed {
    return $this->getValue()->{ParagraphSettingTypesInterface::CSS_CLASS_SETTING_NAME} ?? '';
  }

  /**
   * Stored classes value normalised to an array of strings.
   *
   * @return string[]
   *   List of class names extracted from the field value.
   */
  protected function getClassesArrayValue(): array {
    $classes_value = $this->getClassesValue();

    if (is_object($classes_value)) {
      return array_values(get_object_vars($classes_value));
    }
    if (is_string($classes_value)) {
      $exploded = explode(self::CSS_CLASS_DELIMITER, $classes_value);
      return $exploded === [''] ? [] : $exploded;
    }
    if (is_array($classes_value)) {
      return $classes_value;
    }

    return [];
  }

  /**
   * Process default classes against the configured class list.
   *
   * Ensures every option group either has one of its options set or falls
   * back to the configured default.
   *
   * @param string[] $classes
   *   Classes stored in the field value (mutated in place).
   */
  protected function processDefaultClasses(array &$classes): void {
    $defaults = $this->getStorageItemDefaultClasses(ParagraphSettingTypesInterface::CSS_CLASS_SETTING_NAME);
    foreach ($defaults as $modifier) {
      $default = (string) $modifier['default'];
      $existing_classes = array_intersect($modifier['options'], $classes);
      if ($existing_classes === []) {
        $classes[] = $default;
        continue;
      }
      if (count($existing_classes) <= 1) {
        continue;
      }
      // Strip an accidentally-added default when another concrete option was
      // also selected for the same modifier group.
      if (in_array($default, $existing_classes, TRUE)) {
        $key = array_search($default, $classes, TRUE);
        if ($key !== FALSE) {
          unset($classes[$key]);
        }
      }
    }
  }

  /**
   * Persist `$values` as a JSON-encoded string on the underlying field.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  protected function setEncodedValue(mixed $values): void {
    $this->setValue($this->toEncodedValue($values), TRUE);
  }

  /**
   * Wrap an arbitrary value in the storage shape.
   *
   * @return array{0: array{value: string|false}}
   *   Storage-shaped array, ready for FieldItemList::setValue().
   */
  protected function toEncodedValue(mixed $value): array {
    return [
      ['value' => json_encode($value)],
    ];
  }

  /**
   * Storage item default value (NULL if the plugin can't be loaded).
   */
  protected function getStorageItemDefaultValue(string $storage_id): mixed {
    try {
      return $this->loadStorageItemById($storage_id)->getDefaultValue();
    }
    catch (PluginException) {
      return NULL;
    }
  }

  /**
   * Storage item default classes — one entry per select-style child plugin.
   *
   * @return array<int, array{options: array<int|string>, default: mixed}>
   *   One entry per select-style child plugin, keyed by group index.
   */
  protected function getStorageItemDefaultClasses(string $storage_id): array {
    $defaults = [];

    try {
      $plugin = $this->loadStorageItemById($storage_id);
      foreach ($plugin->getChildrenPlugins() as $child_plugin) {
        if ($child_plugin instanceof ParagraphSettingSelectInterface) {
          $defaults[] = [
            'options' => array_keys($child_plugin->getOptions()),
            'default' => $child_plugin->getDefaultValue(),
          ];
        }
      }
    }
    catch (PluginException $exception) {
      $this->getLogger()->error($exception->getMessage());
    }

    return $defaults;
  }

  /**
   * Load a paragraph setting plugin by id.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function loadStorageItemById(string $storage_id): ParagraphSettingInterface {
    return $this->getPluginManager()->getPluginById($storage_id);
  }

  /**
   * Lazy plugin manager getter (field item list cannot accept DI).
   */
  protected function getPluginManager(): ParagraphSettingPluginManagerInterface {
    /** @var \Drupal\d_p\ParagraphSettingPluginManagerInterface $manager */
    $manager = $this->pluginManager
      ??= \Drupal::service('d_p.paragraph_settings.plugin.manager');
    return $manager;
  }

  /**
   * Lazy logger getter (field item list cannot accept DI).
   */
  protected function getLogger(): LoggerInterface {
    return $this->logger ??= \Drupal::logger(self::LOGGER_CHANNEL);
  }

}
