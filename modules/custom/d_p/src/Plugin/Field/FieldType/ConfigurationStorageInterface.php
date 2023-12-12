<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface;

/**
 * Provides interface for configuration storage field.
 */
interface ConfigurationStorageInterface {

  /**
   * Gets the settings field from a given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Fieldable entity.
   *
   * @return \Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface|null
   *   Field, defaults to null.
   *
   * @throws \Drupal\d_p\Exception\MissingConfigurationStorageFieldException
   */
  public static function getSettingsFieldFromEntity(FieldableEntityInterface $entity): ?ConfigurationStorageFieldItemListInterface;

}
