<?php

namespace Drupal\search_api\Plugin\views\field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\search_api\Plugin\views\SearchApiHandlerTrait;
use Drupal\search_api\Processor\ConfigurablePropertyInterface;
use Drupal\search_api\Processor\ProcessorPropertyInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\views\ResultRow;

/**
 * Provides a trait to use for Search API Views field handlers.
 *
 * Multi-valued field handling is taken from
 * \Drupal\views\Plugin\views\field\PrerenderList.
 *
 * Note: Some method parameters are documented as type array|\ArrayAccess. This
 * is just done to avoid the code sniffer complaining about the missing "array"
 * type hint (since it's impossible to add it, due to the Views parent plugin
 * classes not having that type hint, either).
 */
trait SearchApiFieldTrait {

  use SearchApiHandlerTrait;

  /**
   * Contains the properties needed by this field handler.
   *
   * The array is keyed by datasource ID (which might be NULL) and property
   * path, the values are the combined property paths.
   *
   * @var string[][]
   */
  protected $retrievedProperties = [];

  /**
   * The combined property path of this field.
   *
   * @var string|null
   */
  protected $combinedPropertyPath;

  /**
   * The datasource ID of this field, if any.
   *
   * @var string|null
   */
  protected $datasourceId;

  /**
   * Contains overridden values to be returned on the next getValue() call.
   *
   * The values are keyed by the field given as $field in the call, so that it's
   * possible to return different values based on the field.
   *
   * @var array
   *
   * @see SearchApiFieldTrait::getValue()
   */
  protected $overriddenValues = [];

  /**
   * Index in the current row's field values that is currently displayed.
   *
   * @var int
   *
   * @see SearchApiFieldTrait::getEntity()
   */
  protected $valueIndex = 0;

  /**
   * The account to use for access checks for this search.
   *
   * @var \Drupal\Core\Session\AccountInterface|false|null
   *
   * @see \Drupal\search_api\Plugin\views\field\SearchApiFieldTrait::checkEntityAccess()
   */
  protected $accessAccount;

  /**
   * Associative array keyed by property paths for which to skip access checks.
   *
   * Values are all TRUE.
   *
   * @var bool[]
   */
  protected $skipAccessChecks = [];

  /**
   * Array of replacement property paths to use when getting field values.
   *
   * @var string[]
   *
   * @see \Drupal\search_api\Plugin\views\field\SearchApiFieldTrait::extractProcessorProperty()
   */
  protected $propertyReplacements = [];

  /**
   * Cached array of index fields grouped by combined property path.
   *
   * @var \Drupal\search_api\Item\FieldInterface[][]|null
   *
   * @see \Drupal\search_api\Plugin\views\field\SearchApiFieldTrait::getFieldsForPropertyPath()
   */
  protected $fieldsByCombinedPropertyPath;

  /**
   * The fields helper.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface|null
   */
  protected $fieldsHelper;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface|null
   */
  protected $typedDataManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Retrieves the typed data manager.
   *
   * @return \Drupal\Core\TypedData\TypedDataManagerInterface
   *   The typed data manager.
   */
  public function getTypedDataManager() {
    return $this->typedDataManager ?: \Drupal::service('typed_data_manager');
  }

  /**
   * Sets the typed data manager.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The new typed data manager.
   *
   * @return $this
   */
  public function setTypedDataManager(TypedDataManagerInterface $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
    return $this;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * Retrieves the fields helper.
   *
   * @return \Drupal\search_api\Utility\FieldsHelperInterface
   *   The fields helper.
   */
  public function getFieldsHelper() {
    return $this->fieldsHelper ?: \Drupal::service('search_api.fields_helper');
  }

  /**
   * Sets the fields helper.
   *
   * @param \Drupal\search_api\Utility\FieldsHelperInterface $fields_helper
   *   The new fields helper.
   *
   * @return $this
   */
  public function setFieldsHelper(FieldsHelperInterface $fields_helper) {
    $this->fieldsHelper = $fields_helper;
    return $this;
  }

  /**
   * Determines whether this field can have multiple values.
   *
   * When this can't be reliably determined, the method defaults to TRUE.
   *
   * @return bool
   *   TRUE if this field can have multiple values (or if it couldn't be
   *   determined); FALSE otherwise.
   */
  public function isMultiple() {
    return $this instanceof MultiItemsFieldHandlerInterface;
  }

  /**
   * Defines the options used by this plugin.
   *
   * @return array
   *   Returns the options of this handler/plugin.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::defineOptions()
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['link_to_item'] = ['default' => FALSE];
    $options['use_highlighting'] = ['default' => FALSE];

    if ($this->isMultiple()) {
      $options['multi_type'] = ['default' => 'separator'];
      $options['multi_separator'] = ['default' => ', '];
    }

    return $options;
  }

  /**
   * Provide a form to edit options for this plugin.
   *
   * @param array|\ArrayAccess $form
   *   The existing form structure, passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @see \Drupal\views\Plugin\views\ViewsPluginInterface::buildOptionsForm()
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['link_to_item'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link this field to its item'),
      '#description' => $this->t('Display this field as a link to its original entity or item.'),
      '#default_value' => $this->options['link_to_item'],
    ];

    $form['use_highlighting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use highlighted field data'),
      '#description' => $this->t('Display field with matches of the search keywords highlighted, if available.'),
      '#default_value' => $this->options['use_highlighting'],
    ];

    if ($this->isMultiple()) {
      $form['multi_value_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Multiple values handling'),
        '#description' => $this->t('If this field contains multiple values for an item, these settings will determine how they are handled.'),
        '#weight' => 80,
      ];

      $form['multi_type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Display type'),
        '#options' => [
          'ul' => $this->t('Unordered list'),
          'ol' => $this->t('Ordered list'),
          'separator' => $this->t('Simple separator'),
        ],
        '#default_value' => $this->options['multi_type'],
        '#fieldset' => 'multi_value_settings',
        '#weight' => 0,
      ];
      $form['multi_separator'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Separator'),
        '#default_value' => $this->options['multi_separator'],
        '#states' => [
          'visible' => [
            ':input[name="options[multi_type]"]' => ['value' => 'separator'],
          ],
        ],
        '#fieldset' => 'multi_value_settings',
        '#weight' => 1,
      ];
    }
  }

  /**
   * Adds an ORDER BY clause to the query for click sort columns.
   *
   * @param string $order
   *   Either "ASC" or "DESC".
   *
   * @see \Drupal\views\Plugin\views\field\FieldHandlerInterface::clickSort()
   */
  public function clickSort($order) {
    $this->getQuery()->sort($this->definition['search_api field'], $order);
  }

  /**
   * Determines if this field is click sortable.
   *
   * This is the case if this Views field is linked to a certain Search API
   * field.
   *
   * @return bool
   *   TRUE if this field is available for click-sorting, FALSE otherwise.
   *
   * @see \Drupal\views\Plugin\views\field\FieldHandlerInterface::clickSortable()
   */
  public function clickSortable() {
    return !empty($this->definition['search_api field']);
  }

  /**
   * Add anything to the query that we might need to.
   *
   * @see \Drupal\views\Plugin\views\ViewsPluginInterface::query()
   */
  public function query() {
    $combined_property_path = $this->getCombinedPropertyPath();
    $field_id = NULL;
    if (!empty($this->definition['search_api field'])) {
      $field_id = $this->definition['search_api field'];
    }
    $this->addRetrievedProperty($combined_property_path, $field_id);
    if ($this->options['link_to_item']) {
      $this->addRetrievedProperty("$combined_property_path:_object");
    }
  }

  /**
   * Adds a property to be retrieved.
   *
   * @param string $combined_property_path
   *   The combined property path of the property that should be retrieved.
   *   "_object" can be used as a property name to indicate the loaded object is
   *   required.
   * @param string|null $field_id
   *   (optional) The ID of the field corresponding to this property, if any.
   *
   * @return $this
   */
  protected function addRetrievedProperty($combined_property_path, $field_id = NULL) {
    if ($field_id) {
      $this->getQuery()->addRetrievedFieldValue($field_id);
    }

    list($datasource_id, $property_path) = Utility::splitCombinedId($combined_property_path);
    $this->retrievedProperties[$datasource_id][$property_path] = $combined_property_path;
    return $this;
  }

  /**
   * Gets the entity matching the current row and relationship.
   *
   * @param \Drupal\views\ResultRow $values
   *   An object containing all retrieved values.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns the entity matching the values.
   *
   * @see \Drupal\views\Plugin\views\field\FieldHandlerInterface::getEntity()
   */
  public function getEntity(ResultRow $values) {
    $combined_property_path = $this->getCombinedPropertyPath();
    list($datasource_id, $property_path) = Utility::splitCombinedId($combined_property_path);

    if ($values->search_api_datasource !== $datasource_id) {
      return NULL;
    }

    $value_index = $this->valueIndex;
    // Only try two levels. Otherwise, we might end up at an entirely different
    // entity, cause we go too far up.
    $levels = 2;
    while ($levels--) {
      if (!empty($values->_relationship_objects[$combined_property_path][$value_index])) {
        /** @var \Drupal\Core\TypedData\TypedDataInterface $object */
        $object = $values->_relationship_objects[$combined_property_path][$value_index];
        $value = $object->getValue();
        if ($value instanceof EntityInterface) {
          return $value;
        }
      }

      if (!$property_path) {
        break;
      }
      // For multi-valued fields, the parent's index is not the same as the
      // field value's index.
      if (!empty($values->_relationship_parent_indices[$combined_property_path][$value_index])) {
        $value_index = $values->_relationship_parent_indices[$combined_property_path][$value_index];
      }
      list($property_path) = Utility::splitPropertyPath($property_path);
      $combined_property_path = $this->createCombinedPropertyPath($datasource_id, $property_path);
    }

    return NULL;
  }

  /**
   * Gets the value that's supposed to be rendered.
   *
   * This API exists so that other modules can easily set the values of the
   * field without having the need to change the render method as well.
   *
   * Overridden here to provide an easy way to let this method return arbitrary
   * values, without actually touching the $values array.
   *
   * @param \Drupal\views\ResultRow $values
   *   An object containing all retrieved values.
   * @param string $field
   *   Optional name of the field where the value is stored.
   *
   * @see \Drupal\views\Plugin\views\field\FieldHandlerInterface::getValue()
   */
  public function getValue(ResultRow $values, $field = NULL) {
    if (isset($this->overriddenValues[$field])) {
      return $this->overriddenValues[$field];
    }

    return parent::getValue($values, $field);
  }

  /**
   * Runs before any fields are rendered.
   *
   * This gives the handlers some time to set up before any handler has
   * been rendered.
   *
   * @param \Drupal\views\ResultRow[]|\ArrayAccess $values
   *   An array of all ResultRow objects returned from the query.
   *
   * @see \Drupal\views\Plugin\views\field\FieldHandlerInterface::preRender()
   */
  public function preRender(&$values) {
    // We deal with the properties one by one, always loading the necessary
    // values for any nested properties coming afterwards.
    foreach ($this->expandRequiredProperties() as $properties) {
      foreach ($properties as $property_path => $info) {
        $combined_property_path = $info['combined_property_path'];
        $dependents = $info['dependents'];

        if ($combined_property_path === NULL) {
          $this->preLoadResultItems($values, $dependents);
          continue;
        }

        $property_values = $this->getValuesToExtract($values, $combined_property_path, $dependents);
        $this->extractPropertyValues($values, $combined_property_path, $property_values, $dependents);
        $this->checkHighlighting($values, $combined_property_path);
      }
    }
  }

  /**
   * Expands the properties to retrieve for this field.
   *
   * The properties are taken from this object's $retrievedFieldValues property,
   * with all their ancestors also added to the array, with the ancestor
   * properties always ordered before their descendants.
   *
   * This will ensure, when dealing with these properties sequentially, that
   * the parent object necessary to load the "child" property is always already
   * loaded.
   *
   * @return array[][]
   *   The properties to retrieve, keyed by their datasource ID and property
   *   path. The values are associative arrays with the following keys:
   *   - combined_property_path: The "combined property path" of the retrieved
   *     property.
   *   - dependents: An array containing the originally required properties that
   *     led to this property being required.
   */
  protected function expandRequiredProperties() {
    $required_properties = [];
    foreach ($this->retrievedProperties as $datasource_id => $properties) {
      if ($datasource_id === '') {
        $datasource_id = NULL;
      }
      foreach ($properties as $property_path => $combined_property_path) {
        $paths_to_add = [NULL];
        $path_to_add = '';
        foreach (explode(':', $property_path) as $component) {
          $path_to_add .= ($path_to_add ? ':' : '') . $component;
          $paths_to_add[] = $path_to_add;
        }
        foreach ($paths_to_add as $path_to_add) {
          if (!isset($required_properties[$datasource_id][$path_to_add])) {
            $required_properties[$datasource_id][$path_to_add] = [
              'combined_property_path' => $this->createCombinedPropertyPath($datasource_id, $path_to_add),
              'dependents' => [],
            ];
          }
          $required_properties[$datasource_id][$path_to_add]['dependents'][] = $combined_property_path;
        }
      }
    }
    return $required_properties;
  }

  /**
   * Pre-loads the result objects, where necessary.
   *
   * @param \Drupal\views\ResultRow[] $values
   *   The Views result rows for which result objects should be loaded.
   * @param string[] $dependents
   *   The actually required properties (as combined property paths) that
   *   depend on the result objects.
   */
  protected function preLoadResultItems(array $values, array $dependents) {
    $to_load = [];
    foreach ($values as $i => $row) {
      // If the object is already set on the result row, we've got nothing to do
      // here.
      if (!empty($row->_object)) {
        continue;
      }
      // Same if the object was loaded on the result item already.
      $object = $row->_item->getOriginalObject(FALSE);
      if ($object) {
        $row->_object = $object;
        $row->_relationship_objects[NULL] = [$object];
        continue;
      }
      // We also don't need to load the object if all field values that depend
      // on it are already present on the result row.
      $required = FALSE;
      foreach ($dependents as $dependent) {
        if (!isset($row->$dependent)) {
          $required = TRUE;
          break;
        }
      }
      if (!$required) {
        continue;
      }

      $to_load[$row->search_api_id] = $i;
    }

    if (!$to_load) {
      return;
    }

    $items = $this->getIndex()->loadItemsMultiple(array_keys($to_load));
    foreach ($to_load as $item_id => $i) {
      if (!empty($items[$item_id])) {
        $values[$i]->_object = $items[$item_id];
        $values[$i]->_relationship_objects[NULL] = [$items[$item_id]];
      }
    }
  }

  /**
   * Determines and prepares the property values that need to be extracted.
   *
   * @param \Drupal\views\ResultRow[] $values
   *   The Views result rows from which property values should be extracted.
   * @param string $combined_property_path
   *   The combined property path of the property to extract. Or NULL to extract
   *   the result item.
   * @param string[] $dependents
   *   The actually required properties (as combined property paths) that
   *   depend on this property.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface[][]
   *   The values of the property for each result row, keyed by result row
   *   index.
   */
  protected function getValuesToExtract(array $values, $combined_property_path, array $dependents) {
    list ($datasource_id, $property_path) = Utility::splitCombinedId($combined_property_path);

    // Determine the path of the parent property, and the property key to
    // take from it for this property.
    list($parent_path, $name) = Utility::splitPropertyPath($property_path);
    $combined_parent_path = $this->createCombinedPropertyPath($datasource_id, $parent_path);

    // For top-level properties, we need the definition to check whether its
    // a processor-generated property later.
    $property = NULL;
    if (!$parent_path) {
      $datasource_properties = $this->getIndex()
        ->getPropertyDefinitions($datasource_id);
      if (isset($datasource_properties[$name])) {
        $property = $datasource_properties[$name];
      }
    }

    // Now go through all rows and add the property to them, if necessary.
    // We then extract the actual values in a second pass in order to be
    // able to use multi-loading for any encountered entities.
    /** @var \Drupal\Core\TypedData\TypedDataInterface[][] $property_values */
    $property_values = [];
    $entities_to_load = [];
    foreach ($values as $i => $row) {
      // Bail for rows with the wrong datasource for this property, or for
      // which this field doesn't even apply (which will usually be the
      // same, though).
      if (($datasource_id && $datasource_id !== $row->search_api_datasource)
          || !$this->isActiveForRow($row)) {
        continue;
      }

      // Then, make sure we even need this property for the current row.
      // (Will not be the case if all required properties that depend on
      // this property were already set on the row previously.)
      $required = FALSE;
      foreach ($dependents as $dependent) {
        if (!isset($row->$dependent)) {
          $required = TRUE;
          break;
        }
      }
      if (!$required) {
        continue;
      }

      // Check whether there are parent objects present. Otherwise, nothing we
      // can do here.
      if (empty($row->_relationship_objects[$combined_parent_path])) {
        continue;
      }

      // If the property key is "_object", we only needed to load the parent
      // object(s), so we just copy those to the result row object and we're
      // done.
      if ($name === '_object') {
        // The $row->_object is special, since we also set it in
        // \Drupal\search_api\Plugin\views\query\SearchApiQuery::addResults()
        // (conditionally). To keep it consistent, we make it single-valued
        // here, too.
        if ($combined_property_path !== '_object') {
          $row->$combined_property_path = $row->_relationship_objects[$combined_parent_path];
        }
        continue;
      }

      if (empty($row->_relationship_objects[$combined_property_path])) {
        // Check whether this is a processor-generated property and use
        // special code to retrieve it in that case.
        if ($property instanceof ProcessorPropertyInterface) {
          // Determine whether this property is required.
          $is_required = in_array($combined_property_path, $dependents);
          $this->extractProcessorProperty($property, $row, $combined_property_path, $is_required);
          continue;
        }

        foreach ($row->_relationship_objects[$combined_parent_path] as $j => $parent) {
          // Follow references.
          while ($parent instanceof DataReferenceInterface) {
            $parent = $parent->getTarget();
          }

          // At this point we need the parent to be a complex item,
          // otherwise it can't have any children (and thus, our property
          // can't be present).
          if (!($parent instanceof ComplexDataInterface)) {
            continue;
          }

          try {
            // Retrieve the actual typed data for the property and add it to
            // our property values.
            $typed_data = $parent->get($name);
            $property_values[$i][$j] = $typed_data;
            // Remember any encountered entity references so we can
            // multi-load them.
            if ($typed_data instanceof DataReferenceInterface) {
              /** @var \Drupal\Core\TypedData\DataReferenceDefinitionInterface $definition */
              $definition = $typed_data->getDataDefinition();
              $definition = $definition->getTargetDefinition();
              if ($definition instanceof EntityDataDefinitionInterface) {
                $entity_type_id = $definition->getEntityTypeId();
                $entity_type = $this->getEntityTypeManager()
                  ->getDefinition($entity_type_id);
                if ($entity_type->isStaticallyCacheable()) {
                  $entity_id = $typed_data->getTargetIdentifier();
                  $entities_to_load[$entity_type_id][$entity_id] = $entity_id;
                }
              }
            }
          }
          catch (\InvalidArgumentException $e) {
            // This can easily happen, for example, when requesting a field
            // that only exists on a different bundle. Unfortunately, there
            // is no ComplexDataInterface::hasProperty() method, so we can
            // only catch and ignore the exception.
          }
        }
      }
    }

    // Multi-load all entities we encountered before.
    foreach ($entities_to_load as $entity_type_id => $ids) {
      $this->getEntityTypeManager()
        ->getStorage($entity_type_id)
        ->loadMultiple($ids);
    }

    return $property_values;
  }

  /**
   * Extracts a processor-based property from an item.
   *
   * @param \Drupal\search_api\Processor\ProcessorPropertyInterface $property
   *   The property definition.
   * @param \Drupal\views\ResultRow $row
   *   The Views result row.
   * @param string $combined_property_path
   *   The combined property path of the property to set.
   * @param bool $is_required
   *   TRUE if the property is directly required, FALSE if it should only be
   *   extracted because some child/ancestor properties are required.
   */
  protected function extractProcessorProperty(ProcessorPropertyInterface $property, ResultRow $row, $combined_property_path, $is_required) {
    $index = $this->getIndex();
    $processor = $index->getProcessor($property->getProcessorId());
    if (!$processor) {
      return;
    }

    list($datasource_id, $property_path) = Utility::splitCombinedId($combined_property_path);

    // We need to call the processor's addFieldValues() method in order to get
    // the field value. We do this using a clone of the search item so as to
    // preserve the original state of the item. We also use a dummy field
    // object – either a clone of a fitting indexed field (to get its
    // configuration), or a newly created one.
    $property_fields = $this->getFieldsHelper()
      ->filterForPropertyPath($index->getFields(), $datasource_id, $property_path);
    if ($property_fields) {
      if (!empty($this->definition['search_api field'])
          && !empty($property_fields[$this->definition['search_api field']])) {
        $field_id = $this->definition['search_api field'];
        $dummy_field = $property_fields[$field_id];
        // In case this field is also configurable, create a new, unique
        // combined property path for this field so adding multiple fields based
        // on the same property works correctly.
        if ($property instanceof ConfigurablePropertyInterface) {
          $new_path = $combined_property_path . '|' . $field_id;
          $this->propertyReplacements[$combined_property_path] = $new_path;
          $combined_property_path = $new_path;
        }
      }
      else {
        $dummy_field = reset($property_fields);
      }
      $dummy_field = clone $dummy_field;
    }
    else {
      $dummy_field = $this->getFieldsHelper()
        ->createFieldFromProperty($index, $property, $datasource_id, $property_path, 'tmp', 'string');
    }
    /** @var \Drupal\search_api\Item\ItemInterface $dummy_item */
    $dummy_item = clone $row->_item;
    $dummy_item->setFields([
      'tmp' => $dummy_field,
    ]);
    $dummy_item->setFieldsExtracted(TRUE);

    $processor->addFieldValues($dummy_item);

    $row->_relationship_objects[$combined_property_path] = [];
    $set_values = $is_required && !isset($row->{$combined_property_path});
    if ($set_values) {
      $row->$combined_property_path = [];
    }
    foreach ($dummy_field->getValues() as $value) {
      if (!$this->checkEntityAccess($value, $combined_property_path)) {
        continue;
      }
      if ($set_values) {
        $row->{$combined_property_path}[] = $value;
      }
      $typed_data = $this->getTypedDataManager()
        ->create($property, $value);
      $row->_relationship_objects[$combined_property_path][] = $typed_data;
      // Processor-generated properties always have just a single parent: the
      // result item itself. Therefore, the parent's index is always 0.
      $row->_relationship_parent_indices[$combined_property_path][] = 0;
    }
  }

  /**
   * Places extracted property values and objects into the result row.
   *
   * @param \Drupal\views\ResultRow[] $values
   *   The Views result rows from which property values should be extracted.
   * @param string $combined_property_path
   *   The combined property path of the property to extract.
   * @param \Drupal\Core\TypedData\TypedDataInterface[][] $property_values
   *   The values of the property for each result row, keyed by result row
   *   index.
   * @param string[] $dependents
   *   The actually required properties (as combined property paths) that
   *   depend on this property.
   */
  protected function extractPropertyValues(array $values, $combined_property_path, array $property_values, array $dependents) {
    // Now go through the rows a second time and actually add all objects
    // and (if necessary) properties.
    foreach ($values as $i => $row) {
      if (!empty($property_values[$i])) {
        // Add the typed data for the property to our relationship objects
        // for this property path.
        $row->_relationship_objects[$combined_property_path] = [];
        foreach ($property_values[$i] as $j => $typed_data) {
          // If the typed data is an entity, check whether the current
          // user can access it (and switch to the right translation, if
          // available).
          $value = $typed_data->getValue();
          if ($value instanceof EntityInterface) {
            if (!$this->checkEntityAccess($value, $combined_property_path)) {
              continue;
            }
            if ($value instanceof TranslatableInterface
                && $value->hasTranslation($row->search_api_language)) {
              // PhpStorm isn't able to keep both interfaces in mind at the same
              // time, so we need to use a third interface here that combines
              // both.
              /** @var \Drupal\Core\Entity\ContentEntityInterface $value */
              $typed_data = $value->getTranslation($row->search_api_language)
                ->getTypedData();
            }
          }

          // To treat list properties correctly regarding possible child
          // properties, add all the list items individually.
          if ($typed_data instanceof ListInterface) {
            foreach ($typed_data as $item) {
              $row->_relationship_objects[$combined_property_path][] = $item;
              $row->_relationship_parent_indices[$combined_property_path][] = $j;
            }
          }
          else {
            $row->_relationship_objects[$combined_property_path][] = $typed_data;
            $row->_relationship_parent_indices[$combined_property_path][] = $j;
          }
        }
      }

      // Determine whether we want to set field values for this property on this
      // row. This is the case if the property is one of the explicitly
      // retrieved properties and not yet set on the result row object. Also, if
      // we have no objects for this property, we needn't bother anyways, of
      // course.
      if (!in_array($combined_property_path, $dependents)
          || isset($row->$combined_property_path)
          || empty($row->_relationship_objects[$combined_property_path])) {
        continue;
      }

      $row->$combined_property_path = [];

      // Iterate over the typed data objects, extract their values and set
      // the relationship objects for the next iteration of the outer loop
      // over properties.
      foreach ($row->_relationship_objects[$combined_property_path] as $typed_data) {
        $row->{$combined_property_path}[] = $this->getFieldsHelper()
          ->extractFieldValues($typed_data);
      }

      // If we just set any field values on the result row, clean them up
      // by merging them together (currently it's an array of arrays, but
      // it should be just a flat array).
      if ($row->$combined_property_path) {
        $row->$combined_property_path = call_user_func_array('array_merge', $row->$combined_property_path);
      }
    }
  }

  /**
   * Replaces extracted property values with highlighted field values.
   *
   * @param \Drupal\views\ResultRow[] $values
   *   The Views result rows for which highlighted field values should be added
   *   where applicable and possible.
   * @param string $combined_property_path
   *   The combined property path of the property for which to add data.
   */
  protected function checkHighlighting(array $values, $combined_property_path) {
    // If using highlighting data wasn't enabled, we can skip all of this
    // anyways.
    if (empty($this->options['use_highlighting'])) {
      return;
    }

    // Since (currently) only fields can be highlighted, not arbitrary
    // properties, we needn't even bother if there are no matching fields.
    $fields = $this->getFieldsForPropertyPath($combined_property_path);
    if (!$fields) {
      return;
    }

    if (!empty($this->propertyReplacements[$combined_property_path])) {
      $combined_property_path = $this->propertyReplacements[$combined_property_path];
    }

    foreach ($values as $row) {
      // We only want highlighting data if we even wanted (and, thus, extracted)
      // the property's values in the first place.
      if (!isset($row->$combined_property_path)) {
        continue;
      }
      $highlighted_data = $row->_item->getExtraData('highlighted_fields');
      if (!$highlighted_data) {
        continue;
      }
      $highlighted_data = array_intersect_key($highlighted_data, $fields);
      if ($highlighted_data) {
        // There might be multiple fields with highlight data here, in rare
        // cases, but it's unclear how to combine them, or choose one over the
        // other, anyways, so just take the first one.
        $values = reset($highlighted_data);
        $values = $this->combineHighlightedValues($row->$combined_property_path, $values);
        $row->$combined_property_path = $values;
      }
    }
  }

  /**
   * Retrieves all of the index's fields that match the given property path.
   *
   * @param string $combined_property_path
   *   The combined property path to look for.
   *
   * @return \Drupal\search_api\Item\FieldInterface[]
   *   All index fields with the given combined property path, keyed by field
   *   ID.
   */
  protected function getFieldsForPropertyPath($combined_property_path) {
    if (!isset($this->fieldsByCombinedPropertyPath)) {
      $this->fieldsByCombinedPropertyPath = [];
      foreach ($this->getIndex()->getFields() as $field_id => $field) {
        $this->fieldsByCombinedPropertyPath[$field->getCombinedPropertyPath()][$field_id] = $field;
      }
    }
    $this->fieldsByCombinedPropertyPath += [$combined_property_path => []];
    return $this->fieldsByCombinedPropertyPath[$combined_property_path];
  }

  /**
   * Combines raw field values with highlighted ones to get a complete set.
   *
   * If highlighted field values are set on the result item, not all values
   * might be included, but only the ones with matches. Since we still want to
   * show all values, of course, we need to combine the highlighted values with
   * the ones we extracted.
   *
   * @param array $extracted_values
   *   All values for a field.
   * @param array $highlighted_values
   *   A subset of field values that are highlighted.
   *
   * @return array
   *   An array of normal and highlighted field values, avoiding duplicates as
   *   well as possible.
   */
  protected function combineHighlightedValues(array $extracted_values, array $highlighted_values) {
    // Make sure the arrays have consecutive numeric indices. (Is always the
    // case for $extracted_values.)
    $highlighted_values = array_values($highlighted_values);

    // Pre-sanitize the highlighted values with a very permissive setting to
    // make sure the highlighting HTML won't be escaped later.
    foreach ($highlighted_values as $i => $value) {
      if (!($value instanceof MarkupInterface)) {
        $highlighted_values[$i] = $this->sanitizeValue($value, 'xss_admin');
      }
    }

    $extracted_count = count($extracted_values);
    $highlight_count = count($highlighted_values);
    // If there are (at least) as many highlighted values as normal ones, we are
    // done here.
    if ($highlight_count >= $extracted_count) {
      return $highlighted_values;
    }

    // We now compute a "normalized" representation for all (extracted and
    // highlighted) values to be able to find duplicates.
    $normalize = function ($value) {
      $value = (string) $value;
      $value = strip_tags($value);
      $value = html_entity_decode($value);
      return $value;
    };
    $normalized_extracted = array_map($normalize, $extracted_values);
    $normalized_highlighted = array_map($normalize, $highlighted_values);
    $normalized_extracted = array_diff($normalized_extracted, $normalized_highlighted);

    // Make sure that we have no more than $extracted_count values in total.
    while (count($normalized_extracted) + $highlight_count > $extracted_count) {
      array_pop($normalized_extracted);
    }

    // Now combine the two arrays, maintaining the original order by taking a
    // highlighted value only where the extracted value was removed (probably/
    // hopefully by the array_diff()).
    $values = [];
    for ($i = 0; $i < $extracted_count; ++$i) {
      if (isset($normalized_extracted[$i])) {
        $values[] = $extracted_values[$i];
      }
      else {
        $values[] = array_shift($highlighted_values);
      }
    }
    return $values;
  }

  /**
   * Determines whether this field is active for the given row.
   *
   * This is usually determined by the row's datasource.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return bool
   *   TRUE if this field handler might produce output for the given row, FALSE
   *   otherwise.
   */
  protected function isActiveForRow(ResultRow $row) {
    $datasource_ids = [NULL, $row->search_api_datasource];
    return in_array($this->getDatasourceId(), $datasource_ids, TRUE);
  }

  /**
   * Checks whether the searching user has access to the given value.
   *
   * If the value is not an entity, this will always return TRUE.
   *
   * @param mixed $value
   *   The value to check.
   * @param string $property_path
   *   The property path of the value.
   *
   * @return bool
   *   TRUE if the value is not an entity, or the searching user has access to
   *   it; FALSE otherwise.
   */
  protected function checkEntityAccess($value, $property_path) {
    if (!($value instanceof EntityInterface)) {
      return TRUE;
    }
    if (!empty($this->skipAccessChecks[$property_path])) {
      return TRUE;
    }
    if (!isset($this->accessAccount)) {
      $this->accessAccount = $this->getQuery()->getAccessAccount() ?: FALSE;
    }
    return $value->access('view', $this->accessAccount ?: NULL);
  }

  /**
   * Retrieves the combined property path of this field.
   *
   * @return string
   *   The combined property path.
   */
  public function getCombinedPropertyPath() {
    if (!isset($this->combinedPropertyPath)) {
      // Add the property path of any relationships used to arrive at this
      // field.
      $path = $this->realField;
      $relationships = $this->view->relationship;
      $relationship = $this;
      // While doing this, also note which relationships are configured to skip
      // access checks.
      $skip_access = [];
      while (!empty($relationship->options['relationship'])) {
        if (empty($relationships[$relationship->options['relationship']])) {
          break;
        }
        $relationship = $relationships[$relationship->options['relationship']];
        $path = $relationship->realField . ':' . $path;

        foreach ($skip_access as $i => $temp_path) {
          $skip_access[$i] = $relationship->realField . ':' . $temp_path;
        }
        if (!empty($relationship->options['skip_access'])) {
          $skip_access[] = $relationship->realField;
        }
      }
      $this->combinedPropertyPath = $path;
      // Set the field alias to the combined property path so that Views' code
      // can find the raw values, if necessary.
      $this->field_alias = $path;
      // Set the property paths that should skip access checks.
      $this->skipAccessChecks = array_fill_keys($skip_access, TRUE);
    }
    return $this->combinedPropertyPath;
  }

  /**
   * Creates a combined property path.
   *
   * A combined property path is similar to a "combined ID" in that it contains
   * information about both the datasource and the property path on that
   * datasource.
   *
   * The difference is that a combined property path, as used in this class, can
   * be NULL (to reference the original result item).
   *
   * @param string|null $datasource_id
   *   The datasource ID, or NULL for a datasource-independent property.
   * @param string|null $property_path
   *   The property path from the result item to the specified property, or NULL
   *   to reference the result item.
   *
   * @return string|null
   *   The combined property path.
   */
  protected function createCombinedPropertyPath($datasource_id, $property_path) {
    if ($property_path === NULL) {
      return NULL;
    }
    return Utility::createCombinedId($datasource_id, $property_path);
  }

  /**
   * Retrieves the ID of the datasource to which this field belongs.
   *
   * @return string|null
   *   The datasource ID of this field, or NULL if it doesn't belong to a
   *   specific datasource.
   */
  public function getDatasourceId() {
    if (!isset($this->datasourceId)) {
      list($this->datasourceId) = Utility::splitCombinedId($this->getCombinedPropertyPath());
    }
    return $this->datasourceId;
  }

  /**
   * Renders a single item of a row.
   *
   * @param int $count
   *   The index of the item inside the row.
   * @param mixed $item
   *   The item for the field to render.
   *
   * @return string
   *   The rendered output.
   *
   * @see \Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface::render_item()
   */
  public function render_item($count, $item) {
    $this->overriddenValues[NULL] = $item['value'];
    $render = $this->render(new ResultRow());
    $this->overriddenValues = [];
    return $render;
  }

  /**
   * Gets an array of items for the field.
   *
   * Items should be associative arrays with, if possible, "value" as the actual
   * displayable value of the item, plus any items that might be found in the
   * "alter" options array for creating links, etc., such as "path", "fragment",
   * "query", etc. Additionally, items that might be turned into tokens should
   * also be in this array.
   *
   * @param \Drupal\views\ResultRow $values
   *   The result row object containing the values.
   *
   * @return array[]
   *   An array of items for the field, with each item being an array itself.
   *
   * @see \Drupal\views\Plugin\views\field\PrerenderList::getItems()
   */
  public function getItems(ResultRow $values) {
    $property_path = $this->getCombinedPropertyPath();
    if (!empty($this->propertyReplacements[$property_path])) {
      $property_path = $this->propertyReplacements[$property_path];
    }
    if (!empty($values->$property_path)) {
      // Although it's undocumented, the field handler base class assumes items
      // will always be arrays. See #2648012 for documenting this.
      $items = [];
      foreach ((array) $values->$property_path as $i => $value) {
        $item = [
          'value' => $value,
        ];

        if ($this->options['link_to_item']) {
          $item['make_link'] = TRUE;
          $item['url'] = $this->getItemUrl($values, $i);
        }

        $items[] = $item;
      }
      return $items;
    }
    return [];
  }

  /**
   * Renders all items in this field together.
   *
   * @param array|\ArrayAccess $items
   *   The items provided by getItems() for a single row.
   *
   * @return string
   *   The rendered items.
   *
   * @see \Drupal\views\Plugin\views\field\PrerenderList::renderItems()
   */
  public function renderItems($items) {
    if (!empty($items)) {
      if ($this->options['multi_type'] == 'separator') {
        $render = [
          '#type' => 'inline_template',
          '#template' => '{{ items|safe_join(separator) }}',
          '#context' => [
            'items' => $items,
            'separator' => $this->sanitizeValue($this->options['multi_separator'], 'xss_admin'),
          ],
        ];
      }
      else {
        $render = [
          '#theme' => 'item_list',
          '#items' => $items,
          '#title' => NULL,
          '#list_type' => $this->options['multi_type'],
        ];
      }
      return $this->getRenderer()->render($render);
    }
    return '';
  }

  /**
   * Sanitizes the value for output.
   *
   * @param mixed $value
   *   The value being rendered.
   * @param string|null $type
   *   (optional) The type of sanitization needed. If not provided,
   *   \Drupal\Component\Utility\Html::escape() is used.
   *
   * @return \Drupal\views\Render\ViewsRenderPipelineMarkup
   *   Returns the safe value.
   *
   * @see \Drupal\views\Plugin\views\HandlerBase::sanitizeValue()
   */
  public function sanitizeValue($value, $type = NULL) {
    // Pass-through values that are already markup objects.
    if ($value instanceof MarkupInterface) {
      return $value;
    }
    return parent::sanitizeValue($value, $type);
  }

  /**
   * Retrieves an alter options array for linking the given value to its item.
   *
   * @param \Drupal\views\ResultRow $row
   *   The Views result row object.
   * @param int $i
   *   The index in this field's values for which the item link should be
   *   retrieved.
   *
   * @return \Drupal\Core\Url|null
   *   The URL for the specified item, or NULL if it couldn't be found.
   */
  protected function getItemUrl(ResultRow $row, $i) {
    $this->valueIndex = $i;
    if ($entity = $this->getEntity($row)) {
      return $entity->toUrl('canonical');
    }

    if (!empty($row->_relationship_objects[NULL][0])) {
      return $this->getIndex()
        ->getDatasource($row->search_api_datasource)
        ->getItemUrl($row->_relationship_objects[NULL][0]);
    }

    return NULL;
  }

  /**
   * Returns the Render API renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   *
   * @see \Drupal\views\Plugin\views\field\FieldPluginBase::getRenderer()
   */
  abstract protected function getRenderer();

}
