<?php

declare(strict_types=1);

namespace Drupal\d_p\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface;

/**
 * Provides interface for the configuration storage field.
 */
interface ConfigurationStorageInterface {

  /**
   * Gets the settings field from a given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Fieldable entity.
   *
   * @return \Drupal\d_p\Plugin\Field\ConfigurationStorageFieldItemListInterface
   *   The configuration storage field item list.
   *
   * @throws \Drupal\d_p\Exception\MissingConfigurationStorageFieldException
   *   When the entity has no configuration storage field on its bundle.
   */
  public static function getSettingsFieldFromEntity(FieldableEntityInterface $entity): ConfigurationStorageFieldItemListInterface;

}
