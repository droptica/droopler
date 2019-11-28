<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Drupal\d_content_init;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class ContentInitManagerMedia
 *
 * @package Drupal\d_content_init
 */
class ContentInitManagerMedia extends ContentInitManagerBase {

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

    parent::__construct($entity_manager, $serialization, $logger_factory);
  }

  /**
   * Create media content.
   *
   * @param array $media_array
   *   Array with definition of media to create.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created media entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createMediaImage(array $media_array) {
    $media_entity = $this->saveEntity('media', [
      'bundle' => 'd_image',
      'uid' => \Drupal::currentUser()->id(),
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'fields' => $media_array['fields'],
    ]);
    $this->processFields($media_array,$media_entity);
    return $media_entity;
  }

  /**
   * Create a new media entity or return existing one based on image file path.
   *
   * @param string $path
   *   Original file path.
   * @param $destination_directory
   *   Destination dir.
   * @param $alt
   *   Alt text.
   *
   * @return \Drupal\Core\Entity\EntityInterface|void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createMediaImageFromFile($path, $destination_directory, $alt) {
    // Build file URI.
    $file_uri = $this->getFileUri($path, $destination_directory);

    // Check if the media entity exists.
    $existing = $this->getMediaImageByUri($file_uri);
    if ($existing) {
      return $existing;
    }

    // If such entity does not exist, add it and return.
    $file_object = $this->saveFile($path, $file_uri);
    if ($file_object) {
      return $this->createMediaImage([
        'fields' => [
          'field_media_image' => [
            'data' => [
              'target_id' => $file_object->id(),
              'alt' => $alt,
            ],
          ],
        ],
      ]);
    }
    else {
      $this->logger->warning('Cannot save file @file', ['@file' => $path]);
      return;
    }
  }

  /**
   * Get media entity by the file URI.
   *
   * @param string $uri
   * @return \Drupal\Core\Entity\EntityInterface|void
   */
  protected function getMediaImageByUri($uri) {
    return null;
  }

  /**
   * Move file to its destination.
   *
   * @param $path
   *   The original file path.
   *
   * @param $uri
   *   The destination URI.
   *
   * @return \Drupal\file\FileInterface|false
   */
  protected function saveFile($path, $uri) {
    $file_data = file_get_contents($path);
    \Drupal::service('file_system')->prepareDirectory(dirname($uri), FileSystemInterface::CREATE_DIRECTORY);
    return file_save_data($file_data, $uri,FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * Get the file URI.
   *
   * @param string $path
   *   Full file path.
   * @param $directory
   *   Optional subdirectory.
   *
   * @return string
   */
  public function getFileUri($path, $directory) {
    $prefix = 'public://';
    $directory = trim($directory, '/');
    if (!empty($directory)) {
      $directory .= '/';
    }
    return $prefix . $directory . basename($path);
  }
}
