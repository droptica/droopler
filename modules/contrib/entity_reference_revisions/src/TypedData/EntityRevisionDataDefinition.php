<?php

namespace Drupal\entity_reference_revisions\TypedData;

use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;

/**
 * A typed data definition class for describing entities.
 */
class EntityRevisionDataDefinition extends EntityDataDefinition implements EntityDataDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($data_type) {
    $parts = explode(':', $data_type);
    if ($parts[0] != 'entity_revision') {
      throw new \InvalidArgumentException('Data type must be in the form of "entity_revision:ENTITY_TYPE:BUNDLE."');
    }
    $definition = static::create();
    // Set the passed entity type and bundle.
    if (isset($parts[1])) {
      $definition->setEntityTypeId($parts[1]);
    }
    if (isset($parts[2])) {
      $definition->setBundles(array($parts[2]));
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    $type = 'entity_revision';
    if ($entity_type = $this->getEntityTypeId()) {
      $type .= ':' . $entity_type;
      // Append the bundle only if we know it for sure and it is not the default
      // bundle.
      if (($bundles = $this->getBundles()) && count($bundles) == 1) {
        $bundle = reset($bundles);
        if ($bundle != $entity_type) {
          $type .= ':' . $bundle;
        }
      }
    }
    return $type;
  }
}
