<?php

declare(strict_types = 1);

namespace Drupal\d_content_init;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Content init manager block.
 */
class ContentInitManagerBlock extends ContentInitManagerBase {

  /**
   * Universally Unique IDentifier.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * Discovery and instantiation of block plugins.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Manages the list of available themes.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Content init manager block constructor.
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
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   UUID Interface.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block manager interface.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   Theme handler interface.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SerializationInterface $serialization,
    LoggerChannelFactory $logger_factory,
    AccountProxyInterface $current_user,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    UuidInterface $uuid,
    BlockManagerInterface $block_manager,
    ThemeHandlerInterface $theme_handler) {
    parent::__construct($entity_type_manager, $serialization, $logger_factory, $current_user, $language_manager, $module_handler);
    $this->uuid = $uuid;
    $this->blockManager = $block_manager;
    $this->themeHandler = $theme_handler;
  }

  /**
   * Create block (plugin type).
   *
   * @param array $block
   *   Array with definition of block to create.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|null
   *   Created block entity.
   */
  protected function createBlockPlugin(array $block) {
    try {
      $values = $this->getBaseBlockValues($block['info']);
      $values['id'] = $values['id'] ?? 'block_plugin_' . str_replace('-', '_', $this->uuid->generate());
      $this->getCurrentThemeIfNotDefined($values);
      return $this->saveEntity('block', $values);
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
   * Create block content.
   *
   * @param array $block
   *   Array with definition of block to create.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Created block entity.
   */
  protected function createBlockContent(array $block) {
    try {
      $block_entity = $this->saveEntity('block_content', [
        'type' => $block['info']['bundle'],
        'info' => $block['info']['title'],
      ]);
      $this->processFields($block, $block_entity);
      $this->placeBlockContent($block, $block_entity);
      return $block_entity;
    }
    catch (PluginNotFoundException $e) {
      $this->logger->error('Create block: Entity type "block_content" doesn\'t exist.');
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logger->error('Create block: Entity type "block_content" storage handler couldn\'t be loaded.');
    }
    catch (EntityStorageException $e) {
      $this->logger->error('Create block: Entity of type "block_content" couldn\'t be handled.');
    }
    return NULL;
  }

  /**
   * Create block plugin instance for the block content and place it in region.
   *
   * @param array $block
   *   Block content definition.
   * @param \Drupal\Core\Entity\EntityInterface $block_entity
   *   Block content entity.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   Created block entity or FALSE.
   */
  protected function placeBlockContent(array $block, EntityInterface $block_entity) {
    try {
      if (isset($block['placement'])) {
        $values = [
          'id' => 'block_content_' . str_replace('-', '_', $block_entity->uuid()),
          'plugin' => 'block_content:' . $block_entity->uuid(),
        ] + $this->getBaseBlockValues($block['placement']);

        $this->getCurrentThemeIfNotDefined($values);
        return $this->saveEntity('block', $values);
      }
      return FALSE;
    }
    catch (PluginNotFoundException $e) {
      $this->logger->error('Place block: Entity type "block_content" doesn\'t exist.');
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logger->error('Place block: Entity type "block_content" storage handler couldn\'t be loaded.');
    }
    catch (EntityStorageException $e) {
      $this->logger->error('Place block: Entity of type "block_content" couldn\'t be handled.');
    }
    return FALSE;
  }

  /**
   * Get base block values.
   *
   * @param array $block_info
   *   Array with block info.
   *
   * @return array
   *   Array of base values.
   */
  protected function getBaseBlockValues(array $block_info) {
    return array_intersect_key($block_info, array_flip([
      'id',
      'plugin',
      'langcode',
      'status',
      'theme',
      'region',
      'weight',
      'settings',
    ]));
  }

  /**
   * Get current theme if has not been defined for this block.
   *
   * @param array $block_values
   *   Block values.
   */
  protected function getCurrentThemeIfNotDefined(array &$block_values) {
    if (!isset($block_values['theme']) || empty($block_values['theme'])) {
      $block_values['theme'] = $this->themeHandler->getDefault();
    }
  }

}
