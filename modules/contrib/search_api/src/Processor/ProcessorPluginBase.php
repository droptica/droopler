<?php

namespace Drupal\search_api\Processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\IndexPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class from which other processors may extend.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_processor_info_alter(). The definition includes the following
 * keys:
 * - id: The unique, system-wide identifier of the processor.
 * - label: The human-readable name of the processor, translated.
 * - description: A human-readable description for the processor, translated.
 * - stages: The default weights for all stages for which the processor should
 *   run. Available stages are defined by the STAGE_* constants in
 *   ProcessorInterface. This is, by default, used for supportsStage(), so if
 *   you don't provide a value here, your processor might not work as expected
 *   even though it implements the corresponding method.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiProcessor(
 *   id = "my_processor",
 *   label = @Translation("My Processor"),
 *   description = @Translation("Does … something."),
 *   stages = {
 *     "preprocess_index" = 0,
 *     "preprocess_query" = 0,
 *     "postprocess_query" = 0,
 *   }
 * )
 * @endcode
 *
 * @see \Drupal\search_api\Annotation\SearchApiProcessor
 * @see \Drupal\search_api\Processor\ProcessorPluginManager
 * @see \Drupal\search_api\Processor\ProcessorInterface
 * @see plugin_api
 */
abstract class ProcessorPluginBase extends IndexPluginBase implements ProcessorInterface {

  /**
   * The fields helper.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface|null
   */
  protected $fieldsHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setFieldsHelper($container->get('search_api.fields_helper'));

    return $processor;
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
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsStage($stage) {
    $plugin_definition = $this->getPluginDefinition();
    return isset($plugin_definition['stages'][$stage]);
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight($stage) {
    if (isset($this->configuration['weights'][$stage])) {
      return $this->configuration['weights'][$stage];
    }
    $plugin_definition = $this->getPluginDefinition();
    if (isset($plugin_definition['stages'][$stage])) {
      return (int) $plugin_definition['stages'][$stage];
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($stage, $weight) {
    $this->configuration['weights'][$stage] = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return !empty($this->pluginDefinition['locked']);
  }

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    return !empty($this->pluginDefinition['hidden'])
        || !empty($this->pluginDefinition['no_ui']);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {}

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {}

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {}

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {}

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {}

  /**
   * {@inheritdoc}
   */
  public function postprocessSearchResults(ResultSetInterface $results) {}

  /**
   * {@inheritdoc}
   */
  public function requiresReindexing(array $old_settings = NULL, array $new_settings = NULL) {
    // Only require re-indexing for processors that actually run during the
    // indexing process.
    return $this->supportsStage(ProcessorInterface::STAGE_PREPROCESS_INDEX);
  }

  /**
   * Ensures that a field with certain properties is indexed on the index.
   *
   * Can be used as a helper method in preIndexSave().
   *
   * @param string|null $datasource_id
   *   The ID of the field's datasource, or NULL for a datasource-independent
   *   field.
   * @param string $property_path
   *   The field's property path on the datasource.
   * @param string|null $type
   *   (optional) If set, the field should have this type.
   *
   * @return \Drupal\search_api\Item\FieldInterface
   *   A field on the index, possibly newly added, with the specified
   *   properties.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if there is no property with the specified path, or no type is
   *   given and no default could be determined for the property.
   */
  protected function ensureField($datasource_id, $property_path, $type = NULL) {
    $field = $this->findField($datasource_id, $property_path, $type);

    if (!$field) {
      $properties = $this->index->getPropertyDefinitions($datasource_id);
      $property = $this->getFieldsHelper()
        ->retrieveNestedProperty($properties, $property_path);
      if (!$property) {
        $property_id = Utility::createCombinedId($datasource_id, $property_path);
        $processor_label = $this->label();
        throw new SearchApiException("Could not find property '$property_id' which is required by the '$processor_label' processor.");
      }
      $field = $this->getFieldsHelper()
        ->createFieldFromProperty($this->index, $property, $datasource_id, $property_path, NULL, $type);
      $this->index->addField($field);
    }

    $field->setIndexedLocked();
    if (isset($type)) {
      $field->setTypeLocked();
    }
    return $field;
  }

  /**
   * Finds a certain field in the index.
   *
   * @param string|null $datasource_id
   *   The ID of the field's datasource, or NULL for a datasource-independent
   *   field.
   * @param string $property_path
   *   The field's property path on the datasource.
   * @param string|null $type
   *   (optional) If set, only return a field if it has this type.
   *
   * @return \Drupal\search_api\Item\FieldInterface|null
   *   A field on the index with the desired properties, or NULL if none could
   *   be found.
   */
  protected function findField($datasource_id, $property_path, $type = NULL) {
    foreach ($this->index->getFieldsByDatasource($datasource_id) as $field) {
      if ($field->getPropertyPath() === $property_path) {
        if (!isset($type) || $field->getType() === $type) {
          return $field;
        }
      }
    }
    return NULL;
  }

}
