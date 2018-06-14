<?php

namespace Drupal\entity_reference_revisions\Plugin\DataType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityReference;

/**
 * Defines an 'entity_reference_revisions' data type.
 *
 * This serves as 'entity' property of entity reference field items and gets
 * its value set from the parent, i.e. LanguageItem.
 *
 * The plain value of this reference is the entity object, i.e. an instance of
 * \Drupal\Core\Entity\EntityInterface. For setting the value the entity object
 * or the entity ID may be passed.
 *
 * Note that the definition of the referenced entity's type is required, whereas
 * defining referencable entity bundle(s) is optional. A reference defining the
 * type and bundle of the referenced entity can be created as following:
 * @code
 * $definition = \Drupal\Core\Entity\EntityDefinition::create($entity_type)
 *   ->addConstraint('Bundle', $bundle);
 * \Drupal\Core\TypedData\DataReferenceDefinition::create('entity_revision')
 *   ->setTargetDefinition($definition);
 * @endcode
 *
 * @DataType(
 *   id = "entity_revision_reference",
 *   label = @Translation("Entity reference revisions"),
 *   definition_class = "\Drupal\Core\TypedData\DataReferenceDefinition"
 * )
 */
class EntityReferenceRevisions extends EntityReference {

  /**
   * The entity revision ID.
   *
   * @var integer|string
   */
  protected $revision_id;

  /**
   * The entity ID.
   *
   * @var integer|string
   */
  protected $id;

  /**
   * Returns the definition of the referenced entity.
   *
   * @return \Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface
   *   The reference target's definition.
   */
  public function getTargetDefinition() {
    return $this->definition->getTargetDefinition();
  }

  /**
   * Checks whether the target entity has not been saved yet.
   *
   * @return bool
   *   TRUE if the entity is new, FALSE otherwise.
   */
  public function isTargetNew() {
    // If only an ID is given, the reference cannot be a new entity.
    return !isset($this->id) && isset($this->target) && $this->target->getValue()->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    if (!isset($this->target) && isset($this->revision_id)) {
      $storage = \Drupal::entityTypeManager()->getStorage($this->getTargetDefinition()->getEntityTypeId());
      // By default always load the default revision, so caches get used.
      $entity = $storage->load($this->id);
      if ($entity !== NULL && $entity->getRevisionId() != $this->revision_id) {
        // A non-default revision is a referenced, so load this one.
        $entity = $storage->loadRevision($this->revision_id);
      }
      $this->target = isset($entity) ? $entity->getTypedData() : NULL;
    }
    return $this->target;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetIdentifier() {
    if (isset($this->id)) {
      return $this->id;
    }
    elseif ($entity = $this->getValue()) {
      return $entity->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    unset($this->target);
    unset($this->id);
    unset($this->revision_id);

    // Both the entity ID and the entity object may be passed as value. The
    // reference may also be unset by passing NULL as value.
    if (!isset($value)) {
      $this->target = NULL;
    }
    elseif (is_object($value) && $value instanceof EntityInterface) {
      $this->target = $value->getTypedData();
    }
    elseif (!is_scalar($value['target_id']) || !is_scalar($value['target_revision_id']) || $this->getTargetDefinition()->getEntityTypeId() === NULL) {
      throw new \InvalidArgumentException('Value is not a valid entity.');
    }
    else {
      $this->id = $value['target_id'];
      $this->revision_id = $value['target_revision_id'];
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }
}
