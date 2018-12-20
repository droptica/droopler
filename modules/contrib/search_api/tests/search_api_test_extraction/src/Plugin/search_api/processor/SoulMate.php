<?php

namespace Drupal\search_api_test_extraction\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\EntityProcessorProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the user's soul mate node for indexing.
 *
 * @SearchApiProcessor(
 *   id = "search_api_test_extraction_soul_mate",
 *   label = @Translation("Soul mate user"),
 *   description = @Translation("Add the user with the UID the same as the entity's ID."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SoulMate extends ProcessorPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

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

    $processor->setEntityTypeManager($container->get('entity_type.manager'));
    $processor->setFieldsHelper($container->get('search_api.fields_helper'));

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
    foreach ($index->getDatasources() as $datasource) {
      $entity_type_id = $datasource->getEntityTypeId();
      // The property only works for entities, and doesn't really make sense for
      // users.
      if ($entity_type_id && $entity_type_id !== 'user') {
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

    if ($datasource
        && $datasource->getEntityTypeId()
        && $datasource->getEntityTypeId() !== 'user') {
      $definition = [
        'label' => $this->t('Soul mate'),
        'description' => $this->t("The User with the same UID as the entity's ID"),
        'type' => 'entity:user',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['soul_mate'] = new EntityProcessorProperty($definition);
      $properties['soul_mate']->setEntityTypeId('user');
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();

    if (!($entity instanceof EntityInterface)
        || $entity->getEntityTypeId() === 'user') {
      return;
    }

    /** @var \Drupal\search_api\Item\FieldInterface[][] $to_extract */
    $to_extract = [];
    foreach ($item->getFields() as $field) {
      if (!$field->getDatasourceId()) {
        continue;
      }
      $property_path = $field->getPropertyPath();
      list($direct, $nested) = Utility::splitPropertyPath($property_path, FALSE);
      if ($direct === 'soul_mate') {
        $to_extract[$nested][] = $field;
      }
    }

    if (!$to_extract) {
      return;
    }

    $user = $this->getEntityTypeManager()
      ->getStorage('user')
      ->load($entity->id());
    if (!$user) {
      return;
    }
    $this->getFieldsHelper()
      ->extractFields($user->getTypedData(), $to_extract, $item->getLanguage());
  }

}
