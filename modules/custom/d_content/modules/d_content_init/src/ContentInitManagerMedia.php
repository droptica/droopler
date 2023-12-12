<?php

declare(strict_types = 1);

namespace Drupal\d_content_init;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileRepositoryInterface;

/**
 * Content init media manager.
 */
class ContentInitManagerMedia extends ContentInitManagerBase {

  /**
   * Helper that operate on files.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Provides a file entity repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * Content init manager media constructor.
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
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   Provides a file entity repository.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SerializationInterface $serialization,
    LoggerChannelFactory $logger_factory,
    AccountProxyInterface $current_user,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    FileSystemInterface $file_system,
    FileRepositoryInterface $file_repository) {
    parent::__construct($entity_type_manager, $serialization, $logger_factory, $current_user, $language_manager, $module_handler);
    $this->fileSystem = $file_system;
    $this->fileRepository = $file_repository;
  }

  /**
   * Create media content.
   *
   * @param array $media_array
   *   Array with definition of media to create.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Created media entity.
   */
  protected function createMediaImage(array $media_array) {
    try {
      $media_entity = $this->saveEntity('media', [
        'bundle' => 'd_image',
        'uid' => $this->currentUser->id(),
        'langcode' => $this->languageManager->getDefaultLanguage()->getId(),
        'fields' => $media_array['fields'],
      ]);
      $this->processFields($media_array, $media_entity);
      return $media_entity;
    }
    catch (PluginNotFoundException $e) {
      $this->logger->error('Entity type "media" doesn\'t exist.');
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logger->error('Entity type "media" storage handler couldn\'t be loaded.');
    }
    catch (EntityStorageException $e) {
      $this->logger->error('Media entity couldn\'t be handled.');
    }
    return NULL;
  }

  /**
   * Create a new media entity or return existing one based on image file path.
   *
   * @param string $path
   *   Original file path.
   * @param string $destination_directory
   *   Destination dir.
   * @param string $alt
   *   Alt text.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns Entity object or void.
   */
  public function createMediaImageFromFile($path, $destination_directory, $alt): EntityInterface|null {
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
      return NULL;
    }
  }

  /**
   * Create a new media entity or return existing one based on image fid.
   *
   * @param int $fid
   *   File ID.
   * @param string $alt
   *   Alt text.
   *
   * @return \Drupal\Core\Entity\EntityInterface|void|null
   *   Returns Entity object or void.
   */
  public function createMediaImageFromFid($fid, $alt) {
    // Check if the media entity exists.
    $existing = $this->getMediaImageByFid($fid);
    if ($existing) {
      return $existing;
    }

    // If such entity does not exist, add it and return.
    return $this->createMediaImage([
      'fields' => [
        'field_media_image' => [
          'data' => [
            'target_id' => $fid,
            'alt' => $alt,
          ],
        ],
      ],
    ]);
  }

  /**
   * Get media entity by the file URI.
   *
   * @param string $uri
   *   String containing uri data.
   *
   * @return \Drupal\Core\Entity\EntityInterface|void|null
   *   Returns Entity object or void.
   */
  protected function getMediaImageByUri($uri) {
    try {
      $query = $this->entityTypeManager->getStorage('media')->getQuery();
      $query->condition('bundle', 'd_image');
      $query->condition('field_media_image.0.entity.uri', $uri);
      $query->accessCheck(FALSE);
      $entity_ids = $query->execute();
      $mid = reset($entity_ids);
      return $mid ? $this->entityTypeManager->getStorage('media')->load($mid) : NULL;
    }
    catch (PluginNotFoundException $e) {
      $this->logger->error('Entity type "media" doesn\'t exist.');
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logger->error('Entity type "media" storage handler couldn\'t be loaded.');
    }
    return NULL;
  }

  /**
   * Get media entity by the file ID.
   *
   * @param int $fid
   *   File id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|void|null
   *   Returns Entity object or void.
   */
  protected function getMediaImageByFid($fid) {
    try {
      $query = $this->entityTypeManager->getStorage('media')->getQuery();
      $query->condition('bundle', 'd_image');
      $query->condition('field_media_image', $fid);
      $query->accessCheck(FALSE);
      $entity_ids = $query->execute();
      $mid = reset($entity_ids);
      return $mid ? $this->entityTypeManager->getStorage('media')->load($mid) : NULL;
    }
    catch (PluginNotFoundException $e) {
      $this->logger->error('Entity type "media" doesn\'t exist.');
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logger->error('Entity type "media" storage handler couldn\'t be loaded.');
    }
    return NULL;
  }

  /**
   * Move file to its destination.
   *
   * @param string $path
   *   The original file path.
   * @param string $uri
   *   The destination URI.
   *
   * @return \Drupal\file\FileInterface|false|null
   *   Returns file entity or false.
   */
  protected function saveFile($path, $uri) {
    if (!file_exists($path)) {
      return NULL;
    }
    $file_data = file_get_contents($path);
    $final_dir = dirname($uri);
    $this->fileSystem->prepareDirectory($final_dir, FileSystemInterface::CREATE_DIRECTORY);
    return $this->fileRepository->writeData($file_data, $uri, FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * Get the file target URI.
   *
   * @param string $path
   *   Full file path.
   * @param string $directory
   *   Optional subdirectory.
   *
   * @return string
   *   Returns file uri.
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
