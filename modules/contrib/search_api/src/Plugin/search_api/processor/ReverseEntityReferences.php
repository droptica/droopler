<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\EntityProcessorProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows indexing of reverse entity references.
 *
 * @SearchApiProcessor(
 *   id = "reverse_entity_references",
 *   label = @Translation("Reverse entity references"),
 *   description = @Translation("Allows indexing of entities that link to the indexed entity."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 * )
 */
class ReverseEntityReferences extends ProcessorPluginBase {

  /**
   * Static cache for all entity references.
   *
   * @var array[][]|null
   *
   * @see \Drupal\search_api\Plugin\search_api\processor\ReverseEntityReferences::getEntityReferences()
   */
  protected $references;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager|null
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|null
   */
  protected $entityTypeBundleInfo;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|null
   */
  protected $languageManager;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|null
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setEntityTypeManager($container->get('entity_type.manager'));
    $processor->setEntityFieldManager($container->get('entity_field.manager'));
    $processor->setEntityTypeBundleInfo($container->get('entity_type.bundle.info'));
    $processor->setLanguageManager($container->get('language_manager'));
    $processor->setCache($container->get('cache.default'));

    return $processor;
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
   *   The new entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * Retrieves the entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManager
   *   The entity field manager.
   */
  public function getEntityFieldManager() {
    return $this->entityFieldManager ?: \Drupal::service('entity_field.manager');
  }

  /**
   * Sets the entity field manager.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The new entity field manager.
   *
   * @return $this
   */
  public function setEntityFieldManager(EntityFieldManager $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
    return $this;
  }

  /**
   * Retrieves the entity type bundle info.
   *
   * @return \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   The entity type bundle info.
   */
  public function getEntityTypeBundleInfo() {
    return $this->entityTypeBundleInfo ?: \Drupal::service('entity_type.bundle.info');
  }

  /**
   * Sets the entity type bundle info.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The new entity type bundle info.
   *
   * @return $this
   */
  public function setEntityTypeBundleInfo(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    return $this;
  }

  /**
   * Retrieves the language manager.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  public function getLanguageManager() {
    return $this->languageManager ?: \Drupal::service('language_manager');
  }

  /**
   * Sets the language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The new language manager.
   *
   * @return $this
   */
  public function setLanguageManager(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
    return $this;
  }

  /**
   * Retrieves the cache.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache.
   */
  public function getCache() {
    return $this->cache ?: \Drupal::service('cache.default');
  }

  /**
   * Sets the cache.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The new cache.
   *
   * @return $this
   */
  public function setCache(CacheBackendInterface $cache) {
    $this->cache = $cache;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource || !$datasource->getEntityTypeId()) {
      return $properties;
    }

    $references = $this->getEntityReferences();
    $entity_type_id = $datasource->getEntityTypeId();
    if (isset($references[$entity_type_id])) {
      foreach ($references[$entity_type_id] as $key => $reference) {
        $entity_type_id = $reference['entity_type'];
        try {
          $entity_type = $this->getEntityTypeManager()
            ->getDefinition($entity_type_id);
        }
        catch (PluginNotFoundException $e) {
          continue;
        }
        $args = [
          '%entity_type' => $entity_type->getLabel(),
          '%property' => $reference['label'],
        ];
        $definition = [
          'label' => $this->t('Reverse reference: %entity_type using %property', $args),
          'description' => $this->t("All %entity_type entities that reference this item via the %property field."),
          'type' => "entity:$entity_type_id",
          'processor_id' => $this->getPluginId(),
          // We can't really know whether this will end up being multi-valued, so
          // we err on the side of caution.
          'is_list' => TRUE,
        ];
        $property = new EntityProcessorProperty($definition);
        $property->setEntityTypeId($entity_type_id);
        $properties["search_api_reverse_entity_references_$key"] = $property;
      }
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    try {
      $entity = $item->getOriginalObject()->getValue();
    }
    catch (SearchApiException $e) {
      return;
    }

    if (!($entity instanceof EntityInterface)) {
      return;
    }
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    $langcode = $entity->language()->getId();
    $datasource_id = $item->getDatasourceId();

    /** @var \Drupal\search_api\Item\FieldInterface[][][] $to_extract */
    $to_extract = [];
    $prefix = 'search_api_reverse_entity_references_';
    $prefix_length = strlen($prefix);
    foreach ($item->getFields() as $field) {
      $property_path = $field->getPropertyPath();
      list($direct, $nested) = Utility::splitPropertyPath($property_path, FALSE);
      if ($field->getDatasourceId() === $datasource_id
          && substr($direct, 0, $prefix_length) === $prefix) {
        $property_name = substr($direct, $prefix_length);
        $to_extract[$property_name][$nested][] = $field;
      }
    }

    $references = $this->getEntityReferences();
    foreach ($to_extract as $property_name => $fields_to_extract) {
      if (!isset($references[$entity_type_id][$property_name])) {
        continue;
      }
      $property_info = $references[$entity_type_id][$property_name];

      try {
        $storage = $this->getEntityTypeManager()
          ->getStorage($property_info['entity_type']);
      }
      // @todo Replace with multi-catch once we depend on PHP 7.1+.
      catch (InvalidPluginDefinitionException $e) {
        continue;
      }
      catch (PluginNotFoundException $e) {
        continue;
      }
      $entity_ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($property_info['property'], $entity_id)
        ->execute();

      $entities = $storage->loadMultiple($entity_ids);
      if (!$entities) {
        continue;
      }

      // This is a pretty hack-y work-around to make property extraction work for
      // Views fields, too. In general, adding entities as field values is a
      // pretty bad idea, so this might blow up in some use cases. Just do it for
      // now and hope for the best.
      if (isset($fields_to_extract[''])) {
        foreach ($fields_to_extract[''] as $field) {
          $field->setValues(array_values($entities));
        }
        unset($fields_to_extract['']);
      }
      foreach ($entities as $referencing_entity) {
        $typed_data = $referencing_entity->getTypedData();
        $this->getFieldsHelper()
          ->extractFields($typed_data, $fields_to_extract, $langcode);
      }
    }
  }

  /**
   * Collects all entity references.
   *
   * @return array[][]
   *   An associative array of entity reference information keyed by the
   *   referenced entity type's ID and a custom identifier for the property
   *   (consisting of referencing entity type and property name), with values
   *   being associative arrays with the following keys:
   *   - label: The property label.
   *   - entity_type: The referencing entity type.
   *   - property: The property name.
   */
  public function getEntityReferences() {
    if ($this->references !== NULL) {
      return $this->references;
    }

    // Property labels differ by language, so we need to vary the cache
    // according to the current language.
    $langcode = $this->getLanguageManager()->getCurrentLanguage()->getId();
    $cid = "search_api:reverse_entity_references:$langcode";
    $cache = $this->getCache()->get($cid);
    if (isset($cache->data)) {
      $this->references = $cache->data;
    }
    else {
      $this->references = [];

      $entity_types = $this->getEntityTypeManager()->getDefinitions();
      $field_manager = $this->getEntityFieldManager();
      $entity_type_bundle_info = $this->getEntityTypeBundleInfo();
      foreach ($entity_types as $entity_type_id => $entity_type) {
        if (!($entity_type instanceof ContentEntityTypeInterface)) {
          continue;
        }
        /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $properties */
        $properties = $field_manager->getBaseFieldDefinitions($entity_type_id);
        $bundles = $entity_type_bundle_info->getBundleInfo($entity_type_id);
        foreach ($bundles as $bundle => $info) {
          $properties += $field_manager->getFieldDefinitions($entity_type_id, $bundle);
        }

        foreach ($properties as $name => $property) {
          if ($property->getType() !== 'entity_reference') {
            continue;
          }
          $settings = $property->getSettings();
          if (empty($settings['target_type'])) {
            continue;
          }
          $this->references[$settings['target_type']]["{$entity_type_id}__$name"] = [
            'label' => $property->getLabel(),
            'entity_type' => $entity_type_id,
            'property' => $name,
          ];
        }
      }

      $tags = [
        'entity_types',
        'entity_bundles',
        'entity_field_info',
      ];
      $this->getCache()->set($cid, $this->references, Cache::PERMANENT, $tags);
    }

    return $this->references;
  }

}
