<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds all ancestors' IDs to a hierarchical field.
 *
 * @SearchApiProcessor(
 *   id = "hierarchy",
 *   label = @Translation("Index hierarchy"),
 *   description = @Translation("Allows the indexing of values along with all their ancestors for hierarchical fields (like taxonomy term references)"),
 *   stages = {
 *     "preprocess_index" = -45
 *   }
 * )
 */
class AddHierarchy extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * Static cache for getHierarchyFields() return values, keyed by index ID.
   *
   * @var string[][][]
   *
   * @see \Drupal\search_api\Plugin\search_api\processor\AddHierarchy::getHierarchyFields()
   */
  protected static $indexHierarchyFields = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setEntityTypeManager($container->get('entity_type.manager'));

    return $processor;
  }

  /**
   * Retrieves the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Sets the entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $processor = new static(['#index' => $index], 'hierarchy', []);
    return (bool) $processor->getHierarchyFields();
  }

  /**
   * Finds all (potentially) hierarchical fields for this processor's index.
   *
   * Fields are returned if:
   * - they point to an entity type; and
   * - that entity type contains a property referencing the same type of entity
   *   (so that a hierarchy could be built from that nested property).
   *
   * @return string[][]
   *   An array containing all fields of the index for which hierarchical data
   *   might be retrievable. The keys are those field's IDs, the values are
   *   associative arrays containing the nested properties of those fields from
   *   which a hierarchy might be constructed, with the property paths as the
   *   keys and labels as the values.
   */
  protected function getHierarchyFields() {
    if (!isset(static::$indexHierarchyFields[$this->index->id()])) {
      $field_options = [];

      foreach ($this->index->getFields() as $field_id => $field) {
        $definition = $field->getDataDefinition();
        if ($definition instanceof ComplexDataDefinitionInterface) {
          $properties = $this->getFieldsHelper()
            ->getNestedProperties($definition);
          // The property might be an entity data definition itself.
          $properties[''] = $definition;
          foreach ($properties as $property) {
            $property_label = $property->getLabel();
            $property = $this->getFieldsHelper()->getInnerProperty($property);
            if ($property instanceof EntityDataDefinitionInterface) {
              $options = static::findHierarchicalProperties($property, $property_label);
              if ($options) {
                $field_options += [$field_id => []];
                $field_options[$field_id] += $options;
              }
            }
          }
        }
      }

      static::$indexHierarchyFields[$this->index->id()] = $field_options;
    }

    return static::$indexHierarchyFields[$this->index->id()];
  }

  /**
   * Finds all hierarchical properties nested on an entity-typed property.
   *
   * @param \Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface $property
   *   The property to be searched for hierarchical nested properties.
   * @param string $property_label
   *   The property's label.
   *
   * @return string[]
   *   An options list of hierarchical properties, keyed by the parent
   *   property's entity type ID and the nested properties identifier,
   *   concatenated with a dash (-).
   */
  protected function findHierarchicalProperties(EntityDataDefinitionInterface $property, $property_label) {
    $entity_type_id = $property->getEntityTypeId();
    $property_label = Utility::escapeHtml($property_label);
    $options = [];

    // Check properties for potential hierarchy. Check two levels down, since
    // Core's entity references all have an additional "entity" sub-property for
    // accessing the actual entity reference, which we'd otherwise miss.
    foreach ($this->getFieldsHelper()->getNestedProperties($property) as $name_2 => $property_2) {
      $property_2_label = $property_2->getLabel();
      $property_2 = $this->getFieldsHelper()->getInnerProperty($property_2);
      $is_reference = FALSE;
      if ($property_2 instanceof EntityDataDefinitionInterface) {
        if ($property_2->getEntityTypeId() == $entity_type_id) {
          $is_reference = TRUE;
        }
      }
      elseif ($property_2 instanceof ComplexDataDefinitionInterface) {
        foreach ($property_2->getPropertyDefinitions() as $property_3) {
          $property_3 = $this->getFieldsHelper()->getInnerProperty($property_3);
          if ($property_3 instanceof EntityDataDefinitionInterface) {
            if ($property_3->getEntityTypeId() == $entity_type_id) {
              $is_reference = TRUE;
              break;
            }
          }
        }
      }
      if ($is_reference) {
        $property_2_label = Utility::escapeHtml($property_2_label);
        $options["$entity_type_id-$name_2"] = $property_label . ' » ' . $property_2_label;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form['#description'] = $this->t('Select the fields to which hierarchical data should be added.');

    foreach ($this->getHierarchyFields() as $field_id => $options) {
      $enabled = !empty($this->configuration['fields'][$field_id]);
      $form['fields'][$field_id]['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->index->getField($field_id)->getLabel(),
        '#default_value' => $enabled,
      ];
      reset($options);
      $form['fields'][$field_id]['property'] = [
        '#type' => 'radios',
        '#title' => $this->t('Hierarchy property to use'),
        '#description' => $this->t("This field has several nested properties which look like they might contain hierarchy data for the field. Please pick the one that should be used."),
        '#options' => $options,
        '#default_value' => $enabled ? $this->configuration['fields'][$field_id] : key($options),
        '#access' => count($options) > 1,
        '#states' => [
          'visible' => [
            // @todo This shouldn't be dependent on the form array structure.
            //   Use the '#process' trick instead.
            ":input[name=\"processors[hierarchy][settings][fields][$field_id][status]\"]" => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $formState) {
    $fields = [];
    foreach ($formState->getValue('fields', []) as $field_id => $values) {
      if (!empty($values['status'])) {
        if (empty($values['property'])) {
          $formState->setError($form['fields'][$field_id]['property'], $this->t('You need to select a nested property to use for the hierarchy data.'));
        }
        else {
          $fields[$field_id] = $values['property'];
        }
      }
    }
    $formState->setValue('fields', $fields);
    if (!$fields) {
      $formState->setError($form['fields'], $this->t('You need to select at least one field for which to add hierarchy data.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      foreach ($this->configuration['fields'] as $field_id => $property_specifier) {
        $field = $item->getField($field_id);
        if (!$field) {
          continue;
        }
        list ($entity_type_id, $property) = explode('-', $property_specifier);
        foreach ($field->getValues() as $entity_id) {
          $this->addHierarchyValues($entity_type_id, $entity_id, $property, $field);
        }
      }
    }
  }

  /**
   * Adds all ancestors' IDs of the given entity to the given field.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param mixed $entityId
   *   The ID of the entity for which ancestors should be found.
   * @param string $property
   *   The name of the property on the entity type which contains the references
   *   to the parent entities.
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field to which values should be added.
   */
  protected function addHierarchyValues($entityTypeId, $entityId, $property, FieldInterface $field) {
    if ("$entityTypeId-$property" == 'taxonomy_term-parent') {
      /** @var \Drupal\taxonomy\TermStorageInterface $entity_storage */
      $entity_storage = $this->getEntityTypeManager()
        ->getStorage('taxonomy_term');
      $parents = [];
      foreach ($entity_storage->loadParents($entityId) as $term) {
        $parents[] = $term->id();
      }
    }
    else {
      $entity = $this->getEntityTypeManager()
        ->getStorage($entityTypeId)
        ->load($entityId);
      $parents = [];
      if ($entity instanceof ContentEntityInterface) {
        try {
          foreach ($entity->get($property) as $data) {
            $values = static::getFieldsHelper()->extractFieldValues($data);
            $parents = array_merge($parents, $values);
          }
        }
        catch (\InvalidArgumentException $e) {
          // Might happen, for example, if the property only exists on a certain
          // bundle, and this entity has the wrong one.
        }
      }
    }

    foreach ($parents as $parent) {
      if (!in_array($parent, $field->getValues())) {
        $field->addValue($parent);
        $this->addHierarchyValues($entityTypeId, $parent, $property, $field);
      }
    }
  }

}
