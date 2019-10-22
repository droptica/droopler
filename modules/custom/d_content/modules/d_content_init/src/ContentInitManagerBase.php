<?php

namespace Drupal\d_content_init;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ContentInitManagerBase
 *
 * @package Drupal\d_content_init
 */
abstract class ContentInitManagerBase {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serialization;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * ContentInitManagerBase constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Entity manager interface.
   * @param \Drupal\Component\Serialization\SerializationInterface $serialization
   *   Serialization interface.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger channel factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_manager,
    SerializationInterface $serialization,
    LoggerChannelFactory $logger_factory) {
    $this->entityManager = $entity_manager;
    $this->serialization = $serialization;
    $this->logger = $logger_factory->get('d_content_init');
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
      $this->importFromFile($data['file']);
    }
  }

  /**
   * Import content from specified YML file.
   *
   * @param string $file
   *   Path of the YML file to import.
   *
   * @return mixed|bool
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function saveEntity($entity_type, array $values) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entity = $storage->create($values);
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
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function processFields(array $block, EntityInterface &$entity) {
    if (isset($block['fields'])) {
      foreach ($block['fields'] as $field_name => $field) {
        $this->processField($field_name, $field, $entity);
      }
      $entity->save();
    }
  }

  /**
   * Process field on the specified entity.
   *
   * @param string $field_name
   *   Field name to process.
   * @param array $field
   *   Data to save into field.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to operate on.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   Updated entity on success, FALSE otherwise.
   */
  protected function processField($field_name, array $field, EntityInterface &$entity) {
    if (!$entity->hasField($field_name)) {
      // @todo: log list of the missing fields?
      return FALSE;
    }

    switch ($field['type']) {
      default:
        $entity->set($field_name, $field['data']);
        break;
    }
    return $entity;
  }

}
