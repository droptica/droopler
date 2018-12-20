<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\facet_source\SearchApiDisplay;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor that transforms the results to show the list item label.
 *
 * @FacetsProcessor(
 *   id = "list_item",
 *   label = @Translation("List item label"),
 *   description = @Translation("Display the label instead of the key of fields that are a list (such as <em>List (integer)</em>) or <em>List (text)</em>) or a bundle field. Keep in mind that transformations on the source of this field (such as transliteration or ignore characters) may break this functionality."),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class ListItemProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity_type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigManagerInterface $config_manager, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configManager = $config_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $field_identifier = $facet->getFieldIdentifier();
    $entity = 'node';
    $field = FALSE;
    $allowed_values = [];

    // Support multiple entities when using Search API.
    if ($facet->getFacetSource() instanceof SearchApiDisplay) {
      /** @var \Drupal\search_api\Entity\Index $index */
      $index = $facet->getFacetSource()->getIndex();
      /** @var \Drupal\search_api\Item\Field $field */
      $field = $index->getField($field_identifier);

      if (!$field->getDatasourceId()) {
        throw new InvalidProcessorException("This field has no datasource, there is no valid use for this processor with this facet");
      }
      $entity = $field->getDatasource()->getEntityTypeId();
    }
    // If it's an entity base field, we find it in the field definitions.
    // We don't have access to the bundle via SearchApiFacetSourceInterface, so
    // we check the entity's base fields only.
    $base_fields = $this->entityFieldManager->getFieldDefinitions($entity, '');

    // This only works for configurable fields.
    $config_entity_name = sprintf('field.storage.%s.%s', $entity, $field_identifier);
    if (isset($base_fields[$field_identifier])) {
      $field = $base_fields[$field_identifier];
    }
    elseif ($this->configManager->loadConfigEntityByName($config_entity_name) !== NULL) {
      $field = $this->configManager->loadConfigEntityByName($config_entity_name);
    }
    // Fields defined in code don't can't be loaded from storage so check the
    // fields property path and see if its part of the base fields.
    elseif ($field->getDataDefinition() instanceof FieldItemDataDefinition) {
      $fieldDefinition = $field->getDataDefinition()
        ->getFieldDefinition();
      $referenced_entity_name = sprintf(
        'field.storage.%s.%s',
        $fieldDefinition->getTargetEntityTypeId(),
        $fieldDefinition->getName()
      );
      if ($fieldDefinition instanceof BaseFieldDefinition) {
        if (isset($base_fields[$field->getPropertyPath()])) {
          $field = $base_fields[$field->getPropertyPath()];
        }

      }
      // Diggs down to get the referenced field the entity reference is based
      // on.
      elseif ($this->configManager->loadConfigEntityByName($referenced_entity_name) !== NULL) {
        $field = $this->configManager
          ->loadConfigEntityByName($referenced_entity_name);
      }
    }
    if ($field instanceof FieldStorageDefinitionInterface) {
      if ($field->getName() !== 'type') {
        $allowed_values = options_allowed_values($field);
        if (!empty($allowed_values)) {
          return $this->overWriteDisplayValues($results, $allowed_values);
        }
      }
    }
    // If no values are found for the current field, try to see if this is a
    // bundle field.
    $list_bundles = $this->entityTypeBundleInfo->getBundleInfo($entity);
    if (!empty($list_bundles)) {
      foreach ($list_bundles as $key => $bundle) {
        $allowed_values[$key] = $bundle['label'];
      }
      return $this->overWriteDisplayValues($results, $allowed_values);
    }

    return $results;
  }

  /**
   * Overwrite the display value of the result with a new text.
   *
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   An array of results to work on.
   * @param array $replacements
   *   An array of values that contain possible replacements for the orignal
   *   values.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   The changed results.
   */
  protected function overWriteDisplayValues(array $results, array $replacements) {
    /** @var \Drupal\facets\Result\ResultInterface $a */
    foreach ($results as &$a) {
      if (isset($replacements[$a->getRawValue()])) {
        $a->setDisplayValue($replacements[$a->getRawValue()]);
      }
    }
    return $results;
  }

}
