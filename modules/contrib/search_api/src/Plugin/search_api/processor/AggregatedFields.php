<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\search_api\processor\Property\AggregatedFieldProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\Utility;

/**
 * Adds customized aggregations of existing fields to the index.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\Property\AggregatedFieldProperty
 *
 * @SearchApiProcessor(
 *   id = "aggregated_field",
 *   label = @Translation("Aggregated fields"),
 *   description = @Translation("Add customized aggregations of existing fields to the index."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AggregatedFields extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Aggregated field'),
        'description' => $this->t('An aggregation of multiple other fields.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        // Most aggregation types are single-valued, but "Union" isn't, and we
        // can't know which will be picked, so err on the side of caution here.
        'is_list' => TRUE,
      ];
      $properties['aggregated_field'] = new AggregatedFieldProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $fields = $this->index->getFields();
    $aggregated_fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'aggregated_field');
    $required_properties_by_datasource = [
      NULL => [],
      $item->getDatasourceId() => [],
    ];
    foreach ($aggregated_fields as $field) {
      foreach ($field->getConfiguration()['fields'] as $combined_id) {
        list($datasource_id, $property_path) = Utility::splitCombinedId($combined_id);
        $required_properties_by_datasource[$datasource_id][$property_path] = $combined_id;
      }
    }

    $property_values = $this->getFieldsHelper()
      ->extractItemValues([$item], $required_properties_by_datasource)[0];

    $aggregated_fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'aggregated_field');
    foreach ($aggregated_fields as $aggregated_field) {
      $values = [];
      $configuration = $aggregated_field->getConfiguration();
      foreach ($configuration['fields'] as $combined_id) {
        if (!empty($property_values[$combined_id])) {
          $values = array_merge($values, $property_values[$combined_id]);
        }
      }

      switch ($configuration['type']) {
        case 'concat':
          $values = [implode("\n\n", $values)];
          break;

        case 'sum':
          $values = [array_sum($values)];
          break;

        case 'count':
          $values = [count($values)];
          break;

        case 'max':
          if ($values) {
            $values = [max($values)];
          }
          break;

        case 'min':
          if ($values) {
            $values = [min($values)];
          }
          break;

        case 'first':
          if ($values) {
            $values = [reset($values)];
          }
          break;
        case 'last':
          if ($values) {
            $values = [end($values)];
          }
          break;
      }

      // Do not use setValues(), since that doesn't preprocess the values
      // according to their data type.
      foreach ($values as $value) {
        $aggregated_field->addValue($value);
      }
    }
  }

}
