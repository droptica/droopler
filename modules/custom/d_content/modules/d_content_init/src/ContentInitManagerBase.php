<?php

declare(strict_types = 1);

namespace Drupal\d_content_init;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Content init manager base.
 */
abstract class ContentInitManagerBase {

  use StringTranslationTrait;

  /**
   * Manages entity type plugin definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Serialization format.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serialization;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Content init manager base constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity manager interface.
   * @param \Drupal\Component\Serialization\SerializationInterface $serialization
   *   Serialization interface.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger channel factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler interface.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SerializationInterface $serialization,
    LoggerChannelFactory $logger_factory,
    AccountProxyInterface $current_user,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->serialization = $serialization;
    $this->logger = $logger_factory->get('d_content_init');
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Decode specified YML file.
   *
   * @param string $file
   *   Path of the file to read.
   *
   * @return mixed
   *   The decoded data.
   */
  protected function getDecodedYmlFile($file) {
    $content = file_get_contents($file);
    return $content !== FALSE ? $this->serialization->decode($content) : FALSE;
  }

  /**
   * Return the method name for creating a content.
   *
   * @param string $type
   *   Content type.
   *
   * @return string
   *   Method name that will be called to create content.
   */
  protected function getCreateMethodName($type) {
    return 'create' . ucfirst(str_replace(['-', '_', ' '], '', $type));
  }

  /**
   * Import content from YML files.
   *
   * @param array $structure
   *   Structure of the content to import.
   */
  public function importFromFiles(array $structure) {
    foreach ($structure as $data) {
      if (!$this->importFromFile($data['file'])) {
        $this->logger->error($this->t('Entity from @file was not created.', [
          '@file' => $data['file'],
        ]));
      }
    }
  }

  /**
   * Import content from specified YML file.
   *
   * @param string $file
   *   Path of the YML file to import.
   *
   * @return mixed|bool
   *   Return false or create content value.
   */
  protected function importFromFile($file) {
    $content = $this->getDecodedYmlFile($file);
    if (!$content) {
      $this->logger->error($this->t('Decoding of the YML file failed: @file', [
        '@file' => $file,
      ]));
      return FALSE;
    }

    $create = $this->getCreateMethodName($content['info']['type']);
    if (!method_exists($this, $create)) {
      $this->logger->error($this->t('Missing method: @method', [
        '@method' => $create,
      ]));
      return FALSE;
    }
    return $this->$create($content);
  }

  /**
   * Create and save entity of specified type.
   *
   * @param string $entity_type
   *   Type of the entity to create.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name. If the
   *   entity type has bundles, the bundle key has to be specified.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns Entity object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function saveEntity($entity_type, array $values) {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $entity = $storage->create($values);
    if ($entity instanceof EntityPublishedInterface) {
      $entity->setPublished();
    }
    $entity->save();
    return $entity;
  }

  /**
   * Process array of fields on the specified entity.
   *
   * @param array $block
   *   Block definition to process.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to operate on.
   */
  protected function processFields(array $block, EntityInterface &$entity) {
    try {
      if (isset($block['fields'])) {
        foreach ($block['fields'] as $field_name => $field) {
          $this->processField($field_name, $field, $entity);
        }
        $entity->save();
      }
    }
    catch (EntityStorageException $e) {
      $this->logger->error('Unable to process fields for the entity #@id of type @type', [
        '@id' => $entity->id(),
        '@type' => $entity->getEntityType(),
      ]);
    }
  }

  /**
   * Process field on the specified entity.
   *
   * @param string $field_name
   *   Field name to process.
   * @param array $field
   *   Data to save into field.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Entity to operate on.
   *
   * @return bool|\Drupal\Core\Entity\FieldableEntityInterface
   *   Updated entity on success, FALSE otherwise.
   */
  protected function processField($field_name, array $field, FieldableEntityInterface &$entity) {
    if (!$entity->hasField($field_name)) {
      $this->logger->error($this->t('Missing field: @field', [
        '@field' => $field_name,
      ]));
      return FALSE;
    }

    // Allow other modules to alter.
    $this->moduleHandler->alter('init_field', $entity, $field_name, $field);

    // If the field was not processed by any alter, use a standard field "set".
    if (empty($field['processed'])) {
      $entity->set($field_name, $field['data']);
    }
    return $entity;
  }

}
