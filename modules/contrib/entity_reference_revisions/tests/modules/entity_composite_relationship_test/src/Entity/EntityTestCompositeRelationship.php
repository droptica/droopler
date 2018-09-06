<?php

namespace Drupal\entity_composite_relationship_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_reference_revisions\EntityNeedsSaveInterface;
use Drupal\entity_reference_revisions\EntityNeedsSaveTrait;
use Drupal\entity_test\Entity\EntityTestMulRev;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_composite",
 *   label = @Translation("Test entity - composite relationship"),
 *   base_table = "entity_test_composite",
 *   revision_table = "entity_test_composite_revision",
 *   data_table = "entity_test_composite_field_data",
 *   revision_data_table = "entity_test_composite_field_revision",
 *   translatable = TRUE,
 *   entity_revision_parent_type_field = "parent_type",
 *   entity_revision_parent_id_field = "parent_id",
 *   entity_revision_parent_field_name_field = "parent_field_name",
 *   admin_permission = "administer entity_test composite relationship",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   }
 * )
 */
class EntityTestCompositeRelationship extends EntityTestMulRev implements EntityNeedsSaveInterface {

  use EntityNeedsSaveTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['parent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent ID'))
      ->setDescription(t('The ID of the parent entity of which this entity is referenced.'));

    $fields['parent_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent type'))
      ->setDescription(t('The entity parent type to which this entity is referenced.'));

    $fields['parent_field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent field name'))
      ->setDescription(t('The entity parent field name to which this entity is referenced.'));

    return $fields;
  }

}
