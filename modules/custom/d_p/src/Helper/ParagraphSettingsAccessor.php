<?php

declare(strict_types=1);

namespace Drupal\d_p\Helper;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\d_p\Exception\MissingConfigurationStorageFieldException;
use Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface;
use Drupal\d_p\Plugin\Field\FieldType\ConfigurationStorage;

/**
 * Null-safe accessor for paragraph configuration storage settings.
 *
 * `ConfigurationStorage::getSettingsFieldFromEntity()` throws when the entity
 * doesn't carry a configuration-storage field. In every theming/preprocess
 * hook we want the opposite: "give me the field if it exists, otherwise
 * pretend the setting wasn't there". This helper centralises the
 * `try { … } catch (Missing… $e) { return NULL; }` boilerplate.
 *
 * Marked `final` — this is a static value-object utility, not an extension
 * point. Override the underlying
 * `ConfigurationStorage::getSettingsFieldFromEntity()` if you need different
 * lookup behaviour.
 */
final class ParagraphSettingsAccessor {

  /**
   * Lookup the configuration storage field on `$entity`, or NULL if absent.
   */
  public static function field(FieldableEntityInterface $entity): ?ConfigurationStorageFieldItemListInterface {
    try {
      return ConfigurationStorage::getSettingsFieldFromEntity($entity);
    }
    catch (MissingConfigurationStorageFieldException) {
      return NULL;
    }
  }

  /**
   * Read a single setting from `$entity`, falling back to `$default`.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Entity that may carry a paragraph configuration storage field.
   * @param string $setting_name
   *   Setting machine name (e.g. one of
   *   {@see \Drupal\d_p\ParagraphSettingTypesInterface}'s constants).
   * @param mixed $default
   *   Returned when no configuration storage field is attached, or when the
   *   field has no value for `$setting_name`.
   */
  public static function value(
    FieldableEntityInterface $entity,
    string $setting_name,
    mixed $default = NULL,
  ): mixed {
    return self::field($entity)?->getSettingValue($setting_name, $default) ?? $default;
  }

  private function __construct() {
  }

}
