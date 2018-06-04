<?php

namespace Drupal\entity_reference_revisions\Plugin\DataType;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;

/**
 * Defines the "entity" data type.
 *
 * Instances of this class wrap entity objects and allow to deal with entities
 * based upon the Typed Data API.
 *
 * In addition to the "entity" data type, this exposes derived
 * "entity:$entity_type" and "entity:$entity_type:$bundle" data types.
 *
 * @DataType(
 *   id = "entity_revision",
 *   label = @Translation("Entity Revision"),
 *   description = @Translation("All kind of entities with revision information, e.g. nodes, comments or users."),
 *   deriver = "\Drupal\Core\Entity\Plugin\DataType\Deriver\EntityDeriver",
 *   definition_class = "\Drupal\entity_reference_revisions\TypedData\EntityRevisionDataDefinition"
 * )
 */
class EntityRevisionsAdapter extends EntityAdapter implements \IteratorAggregate, ComplexDataInterface {

}
