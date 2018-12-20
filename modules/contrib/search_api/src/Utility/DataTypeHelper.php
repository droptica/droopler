<?php

namespace Drupal\search_api\Utility;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search_api\DataType\DataTypePluginManager;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;

/**
 * Provides helper methods for dealing with Search API data types.
 */
class DataTypeHelper implements DataTypeHelperInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The data type plugin manager.
   *
   * @var \Drupal\search_api\DataType\DataTypePluginManager
   */
  protected $dataTypeManager;

  /**
   * Cache for the field type mapping.
   *
   * @var array|null
   *
   * @see getFieldTypeMapping()
   */
  protected $fieldTypeMapping;

  /**
   * Cache for the fallback data type mapping per index.
   *
   * @var array
   *
   * @see getDataTypeFallbackMapping()
   */
  protected $dataTypeFallbackMapping = [];

  /**
   * Constructs a DataTypeHelper object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\search_api\DataType\DataTypePluginManager $dataTypeManager
   *   The data type plugin manager.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, DataTypePluginManager $dataTypeManager) {
    $this->moduleHandler = $moduleHandler;
    $this->dataTypeManager = $dataTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function isTextType($type, array $textTypes = ['text']) {
    if (in_array($type, $textTypes)) {
      return TRUE;
    }
    $dataType = $this->dataTypeManager->createInstance($type);
    if ($dataType && !$dataType->isDefault()) {
      return in_array($dataType->getFallbackType(), $textTypes);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeMapping() {
    // Check the cache first.
    if (!isset($this->fieldTypeMapping)) {
      // It's easier to write and understand this array in the form of
      // $searchApiFieldType => [$dataTypes] and flip it below.
      $defaultMapping = [
        'text' => [
          'field_item:string_long.string',
          'field_item:text_long.string',
          'field_item:text_with_summary.string',
          'search_api_html',
          'search_api_text',
        ],
        'string' => [
          'string',
          'email',
          'uri',
          'filter_format',
          'duration_iso8601',
          'field_item:path',
        ],
        'integer' => [
          'integer',
          'timespan',
        ],
        'decimal' => [
          'decimal',
          'float',
        ],
        'date' => [
          'date',
          'datetime_iso8601',
          'timestamp',
        ],
        'boolean' => [
          'boolean',
        ],
        // Types we know about but want/have to ignore.
        NULL => [
          'language',
        ],
      ];

      foreach ($defaultMapping as $searchApiType => $dataTypes) {
        foreach ($dataTypes as $dataType) {
          $mapping[$dataType] = $searchApiType;
        }
      }

      // Allow other modules to intercept and define what default type they want
      // to use for their data type.
      $this->moduleHandler->alter('search_api_field_type_mapping', $mapping);

      $this->fieldTypeMapping = $mapping;
    }

    return $this->fieldTypeMapping;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataTypeFallbackMapping(IndexInterface $index) {
    // Check the cache first.
    $indexId = $index->id();
    if (empty($this->dataTypeFallbackMapping[$indexId])) {
      $server = NULL;
      try {
        $server = $index->getServerInstance();
      }
      catch (SearchApiException $e) {
        // If the server isn't available, just ignore it here and return all
        // custom types.
      }
      $this->dataTypeFallbackMapping[$indexId] = [];
      $dataTypes = $this->dataTypeManager->getInstances();
      foreach ($dataTypes as $typeId => $dataType) {
        // We know for sure that we do not need to fall back for the default
        // data types as they are always present and are required to be
        // supported by all backends.
        if (!$dataType->isDefault() && (!$server || !$server->supportsDataType($typeId))) {
          $this->dataTypeFallbackMapping[$indexId][$typeId] = $dataType->getFallbackType();
        }
      }
    }

    return $this->dataTypeFallbackMapping[$indexId];
  }

}
