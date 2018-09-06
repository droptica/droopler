<?php

namespace Drupal\entity_reference_revisions\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\entity_reference_revisions\EntityNeedsSaveInterface;

/**
 * Defines the 'entity_reference_revisions' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 * - target_bundle: (optional): If set, restricts the entity bundles which may
 *   may be referenced. May be set to an single bundle, or to an array of
 *   allowed bundles.
 *
 * @FieldType(
 *   id = "entity_reference_revisions",
 *   label = @Translation("Entity reference revisions"),
 *   description = @Translation("An entity field containing an entity reference to a specific revision."),
 *   category = @Translation("Reference revisions"),
 *   no_ui = FALSE,
 *   class = "\Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem",
 *   list_class = "\Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList",
 *   default_formatter = "entity_reference_revisions_entity_view",
 *   default_widget = "entity_reference_revisions_autocomplete"
 * )
 */
class EntityReferenceRevisionsItem extends EntityReferenceItem implements OptionsProviderInterface, PreconfiguredFieldUiOptionsInterface {

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $options = array();
    foreach ($entity_types as $entity_type) {
      if ($entity_type->isRevisionable()) {
        $options[$entity_type->id()] = $entity_type->getLabel();
      }
    }

    $element['target_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Type of item to reference'),
      '#options' => $options,
      '#default_value' => $this->getSetting('target_type'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    $options = array();

    // Add all the commonly referenced entity types as distinct pre-configured
    // options.
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $common_references = array_filter($entity_types, function (EntityTypeInterface $entity_type) {
      return $entity_type->get('common_reference_revisions_target') && $entity_type->isRevisionable();
    });

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    foreach ($common_references as $entity_type) {

      $options[$entity_type->id()] = [
        'label' => $entity_type->getLabel(),
        'field_storage_config' => [
          'settings' => [
            'target_type' => $entity_type->id(),
          ]
        ]
      ];
      $default_reference_settings = $entity_type->get('default_reference_revision_settings');
      if (is_array($default_reference_settings)) {
        $options[$entity_type->id()] = array_merge($options[$entity_type->id()], $default_reference_settings);
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $target_type_info = \Drupal::entityTypeManager()->getDefinition($settings['target_type']);

    $properties = parent::propertyDefinitions($field_definition);

    if ($target_type_info->getKey('revision')) {
      $target_revision_id_definition = DataReferenceTargetDefinition::create('integer')
        ->setLabel(t('@label revision ID', array('@label' => $target_type_info->getLabel())))
        ->setSetting('unsigned', TRUE);

      $target_revision_id_definition->setRequired(TRUE);
      $properties['target_revision_id'] = $target_revision_id_definition;
    }

    $properties['entity'] = DataReferenceDefinition::create('entity_revision')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(t('The referenced entity revision'))
      // The entity object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create($settings['target_type']));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $target_type = $field_definition->getSetting('target_type');
    $target_type_info = \Drupal::entityTypeManager()->getDefinition($target_type);

    $schema = parent::schema($field_definition);

    if ($target_type_info->getKey('revision')) {
      $schema['columns']['target_revision_id'] = array(
        'description' => 'The revision ID of the target entity.',
        'type' => 'int',
        'unsigned' => TRUE,
      );
      $schema['indexes']['target_revision_id'] = array('target_revision_id');
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values) && !is_array($values)) {
      // If either a scalar or an object was passed as the value for the item,
      // assign it to the 'entity' property since that works for both cases.
      $this->set('entity', $values, $notify);
    }
    else {
      parent::setValue($values, FALSE);
      // Support setting the field item with only one property, but make sure
      // values stay in sync if only property is passed.
      // NULL is a valid value, so we use array_key_exists().
      if (is_array($values) && array_key_exists('target_id', $values) && !isset($values['entity'])) {
        $this->onChange('target_id', FALSE);
      }
      elseif (is_array($values) && array_key_exists('target_revision_id', $values) && !isset($values['entity'])) {
        $this->onChange('target_revision_id', FALSE);
      }
      elseif (is_array($values) && !array_key_exists('target_id', $values) && !array_key_exists('target_revision_id', $values) && isset($values['entity'])) {
        $this->onChange('entity', FALSE);
      }
      elseif (is_array($values) && array_key_exists('target_id', $values) && isset($values['entity'])) {
        // If both properties are passed, verify the passed values match. The
        // only exception we allow is when we have a new entity: in this case
        // its actual id and target_id will be different, due to the new entity
        // marker.
        $entity_id = $this->get('entity')->getTargetIdentifier();
        // If the entity has been saved and we're trying to set both the
        // target_id and the entity values with a non-null target ID, then the
        // value for target_id should match the ID of the entity value.
        if (!$this->entity->isNew() && $values['target_id'] !== NULL && ($entity_id != $values['target_id'])) {
          throw new \InvalidArgumentException('The target id and entity passed to the entity reference item do not match.');
        }
      }
      // Notify the parent if necessary.
      if ($notify && $this->getParent()) {
        $this->getParent()->onChange($this->getName());
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $values = parent::getValue();
    if ($this->entity instanceof EntityNeedsSaveInterface && $this->entity->needsSave()) {
      $values['entity'] = $this->entity;
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Make sure that the target ID and the target property stay in sync.
    if ($property_name == 'entity') {
      $property = $this->get('entity');
      $target_id = $property->isTargetNew() ? NULL : $property->getTargetIdentifier();
      $this->writePropertyValue('target_id', $target_id);
      $this->writePropertyValue('target_revision_id', $property->getValue()->getRevisionId());
    }
    elseif ($property_name == 'target_id' && $this->target_id != NULL && $this->target_revision_id) {
      $this->writePropertyValue('entity', array(
        'target_id' => $this->target_id,
        'target_revision_id' => $this->target_revision_id,
      ));
    }
    elseif ($property_name == 'target_revision_id' && $this->target_revision_id && $this->target_id) {
      $this->writePropertyValue('entity', array(
        'target_id' => $this->target_id,
        'target_revision_id' => $this->target_revision_id,
      ));
    }
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Avoid loading the entity by first checking the 'target_id'.
    if ($this->target_id !== NULL && $this->target_revision_id !== NULL) {
      return FALSE;
    }
    if ($this->entity && $this->entity instanceof EntityInterface) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $has_new = $this->hasNewEntity();

    // If it is a new entity, parent will save it.
    parent::preSave();

    $is_affected = TRUE;
    if (!$has_new) {
      // Create a new revision if it is a composite entity in a host with a new
      // revision.

      $host = $this->getEntity();
      $needs_save = $this->entity instanceof EntityNeedsSaveInterface && $this->entity->needsSave();

      // The item is considered to be affected if the field is either
      // untranslatable or there are translation changes. This ensures that for
      // translatable fields, a new revision of the referenced entity is only
      // created for the affected translations and that the revision ID does not
      // change on the unaffected translations. In turn, the host entity is not
      // marked as affected for these translations.
      $is_affected = !$this->getFieldDefinition()->isTranslatable() || ($host instanceof TranslatableRevisionableInterface && $host->hasTranslationChanges());
      if ($is_affected && !$host->isNew() && $this->entity && $this->entity->getEntityType()->get('entity_revision_parent_id_field')) {
        if ($host->isNewRevision()) {
          $this->entity->setNewRevision();
          $needs_save = TRUE;
        }
        // Additionally ensure that the default revision state is kept in sync.
        if ($this->entity && $host->isDefaultRevision() != $this->entity->isDefaultRevision()) {
          $this->entity->isDefaultRevision($host->isDefaultRevision());
          $needs_save = TRUE;
        }
      }
      if ($needs_save) {
        $this->entity->save();
      }
    }
    if ($this->entity && $is_affected) {
      $this->target_revision_id = $this->entity->getRevisionId();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);

    $needs_save = FALSE;
    // If any of entity, parent type or parent id is missing then return.
    if (!$this->entity || !$this->entity->getEntityType()->get('entity_revision_parent_type_field') || !$this->entity->getEntityType()->get('entity_revision_parent_id_field')) {
      return;
    }

    $entity = $this->entity;
    $parent_entity = $this->getEntity();

    // If the entity has a parent field name get the key.
    if ($entity->getEntityType()->get('entity_revision_parent_field_name_field')) {
      $parent_field_name = $entity->getEntityType()->get('entity_revision_parent_field_name_field');

      // If parent field name has changed then set it.
      if ($entity->get($parent_field_name)->value != $this->getFieldDefinition()->getName()) {
        $entity->set($parent_field_name, $this->getFieldDefinition()->getName());
        $needs_save = TRUE;
      }
    }

    // Keep in sync the translation languages between the parent and the child.
    // For non translatable fields we have to do this in ::preSave but for
    // translatable fields we have all the information we need in ::delete.
    if (isset($parent_entity->original) && !$this->getFieldDefinition()->isTranslatable()) {
      $langcodes = array_keys($parent_entity->getTranslationLanguages());
      $original_langcodes = array_keys($parent_entity->original->getTranslationLanguages());
      if ($removed_langcodes = array_diff($original_langcodes, $langcodes)) {
        foreach ($removed_langcodes as $removed_langcode) {
          if ($entity->hasTranslation($removed_langcode)) {
            $entity->removeTranslation($removed_langcode);
          }
        }
        $needs_save = TRUE;
      }
    }

    $parent_type = $entity->getEntityType()->get('entity_revision_parent_type_field');
    $parent_id = $entity->getEntityType()->get('entity_revision_parent_id_field');

    // If the parent type has changed then set it.
    if ($entity->get($parent_type)->value != $parent_entity->getEntityTypeId()) {
      $entity->set($parent_type, $parent_entity->getEntityTypeId());
      $needs_save = TRUE;
    }
    // If the parent id has changed then set it.
    if ($entity->get($parent_id)->value != $parent_entity->id()) {
      $entity->set($parent_id, $parent_entity->id());
      $needs_save = TRUE;
    }

    if ($needs_save) {
      // Check if any of the keys has changed, save it, do not create a new
      // revision.
      $entity->setNewRevision(FALSE);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() {
    $child = $this->entity;
    // Return early, and do not delete the child revision, when the child
    // revision is either:
    // 1: Missing.
    // 2: A default revision.
    if (!$child || $child->isDefaultRevision()) {
      return;
    }

    $host = $this->getEntity();
    $field_name = $this->getFieldDefinition()->getName() . '.target_revision_id';
    $all_revisions = \Drupal::entityQuery($host->getEntityTypeId())
      ->condition($field_name, $child->getRevisionId())
      ->allRevisions()
      ->execute();

    if (count($all_revisions) > 1) {
      // Do not delete if there is more than one usage of this revision.
      return;
    }

    \Drupal::entityTypeManager()->getStorage($child->getEntityTypeId())->deleteRevision($child->getRevisionId());
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();

    if ($this->entity && $this->entity->getEntityType()->get('entity_revision_parent_type_field') && $this->entity->getEntityType()->get('entity_revision_parent_id_field')) {
      // Only delete composite entities if the host field is not translatable.
      if (!$this->getFieldDefinition()->isTranslatable()) {
        $this->entity->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function onDependencyRemoval(FieldDefinitionInterface $field_definition, array $dependencies) {
    $changed = FALSE;
    $entity_manager = \Drupal::entityManager();
    $target_entity_type = $entity_manager->getDefinition($field_definition->getFieldStorageDefinition()
      ->getSetting('target_type'));
    $handler_settings = $field_definition->getSetting('handler_settings');

    // Update the 'target_bundles' handler setting if a bundle config dependency
    // has been removed.
    if (!empty($handler_settings['target_bundles'])) {
      if ($bundle_entity_type_id = $target_entity_type->getBundleEntityType()) {
        if ($storage = $entity_manager->getStorage($bundle_entity_type_id)) {
          foreach ($storage->loadMultiple($handler_settings['target_bundles']) as $bundle) {
            if (isset($dependencies[$bundle->getConfigDependencyKey()][$bundle->getConfigDependencyName()])) {
              unset($handler_settings['target_bundles'][$bundle->id()]);
              $changed = TRUE;

              // In case we deleted the only target bundle allowed by the field
              // we can log a message because the behaviour of the field will
              // have changed.
              if ($handler_settings['target_bundles'] === []) {
                \Drupal::logger('entity_reference_revisions')
                  ->notice('The %target_bundle bundle (entity type: %target_entity_type) was deleted. As a result, the %field_name entity reference revisions field (entity_type: %entity_type, bundle: %bundle) no longer specifies a specific target bundle. The field will now accept any bundle and may need to be adjusted.', [
                    '%target_bundle' => $bundle->label(),
                    '%target_entity_type' => $bundle->getEntityType()
                      ->getBundleOf(),
                    '%field_name' => $field_definition->getName(),
                    '%entity_type' => $field_definition->getTargetEntityTypeId(),
                    '%bundle' => $field_definition->getTargetBundle()
                  ]);
              }
            }
          }
        }
      }
    }

    if ($changed) {
      $field_definition->setSetting('handler_settings', $handler_settings);
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');
    $entity_manager = \Drupal::entityTypeManager();

    // Bail if there are no referenceable entities.
    if (!$selection_manager->getSelectionHandler($field_definition)->getReferenceableEntities()) {
      return;
    }

    // ERR field values are never cross referenced so we need to generate new
    // target entities. First, find the target entity type.
    $target_type_id = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    $target_type = $entity_manager->getDefinition($target_type_id);
    $handler_settings = $field_definition->getSetting('handler_settings');

    // Determine referenceable bundles.
    $bundle_manager = \Drupal::service('entity_type.bundle.info');
    if (isset($handler_settings['target_bundles']) && is_array($handler_settings['target_bundles'])) {
      $bundles = $handler_settings['target_bundles'];
    }
    else {
      $bundles = $bundle_manager->getBundleInfo($target_type_id);
    }
    $bundle = array_rand($bundles);

    $label = NULL;
    if ($label_key = $target_type->getKey('label')) {
      $random = new Random();
      // @TODO set the length somehow less arbitrary.
      $label = $random->word(mt_rand(1, 10));
    }

    // Create entity stub.
    $entity = $selection_manager->getSelectionHandler($field_definition)->createNewEntity($target_type_id, $bundle, $label, 0);

    // Populate entity values and save.
    $instances = $entity_manager
      ->getStorage('field_config')
      ->loadByProperties([
        'entity_type' => $target_type_id,
        'bundle' => $bundle,
      ]);

    foreach ($instances as $instance) {
      $field_storage = $instance->getFieldStorageDefinition();
      $max = $cardinality = $field_storage->getCardinality();
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        // Just an arbitrary number for 'unlimited'
        $max = rand(1, 5);
      }
      $field_name = $field_storage->getName();
      $entity->{$field_name}->generateSampleItems($max);
    }

    $entity->save();

    return [
      'target_id' => $entity->id(),
      'target_revision_id' => $entity->getRevisionId(),
    ];
  }

}
